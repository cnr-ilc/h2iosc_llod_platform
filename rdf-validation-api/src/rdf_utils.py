import os
import tempfile
import urllib.parse
import asyncio
import aiohttp
from typing import Dict, Any, List, Tuple, Optional
import rdflib
from rdflib import Graph, URIRef
from rdflib.namespace import DCAT, DCTERMS
import pyshacl
from fastapi import UploadFile
from src.models import (
    RDFFormat, QualityCriteria,
    MIN_EXTERNAL_LINKS_PERCENTAGE, QualityScore
)


def save_upload_file_tmp(upload_file: UploadFile) -> str:
    """Temporarily saves the uploaded file and returns the path"""
    try:
        suffix = os.path.splitext(upload_file.filename)[1]
        with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
            content = upload_file.file.read()
            tmp.write(content)
            tmp_path = tmp.name
        return tmp_path
    finally:
        upload_file.file.seek(0)  # Reset file pointer


def get_rdf_format_from_filename(filename: str) -> RDFFormat:
    """Determines the RDF format based on the file extension"""
    from src.models import EXTENSION_TO_FORMAT
    ext = os.path.splitext(filename)[1].lower()
    return EXTENSION_TO_FORMAT.get(ext, RDFFormat.TURTLE)  # Default to Turtle if the extension is unknown


def extract_declared_base(file_path: str, format: RDFFormat) -> Optional[str]:
    """
    Extract declared base URI from RDF document based on format-specific declarations.
    
    Returns:
        - Optional[str]: The declared base URI if found, otherwise None
    """
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if format == RDFFormat.TURTLE or format == RDFFormat.N3:
            # Look for @base declaration in Turtle/N3
            import re
            base_match = re.search(r'@base\s+<([^>]+)>', content, re.IGNORECASE)
            if base_match:
                return base_match.group(1)
        
        elif format == RDFFormat.RDFXML:
            # Look for xml:base attribute in RDF/XML
            import re
            base_match = re.search(r'xml:base\s*=\s*["\']([^"\']+)["\']', content, re.IGNORECASE)
            if base_match:
                return base_match.group(1)
        
        elif format == RDFFormat.JSONLD:
            # Look for @base in JSON-LD context
            import json
            try:
                data = json.loads(content)
                if isinstance(data, dict):
                    # Check @context for @base
                    context = data.get('@context', {})
                    if isinstance(context, dict) and '@base' in context:
                        return context['@base']
                    # Check root level @base
                    if '@base' in data:
                        return data['@base']
            except json.JSONDecodeError:
                pass
    
    except Exception:
        pass
    
    return None


def check_main_uri(g: Graph, file_path: str = None, format: RDFFormat = None) -> Tuple[bool, List[str], Optional[str]]:
    """
    Checks if the RDF graph has a main URI that describes it by looking for declared base URI.

    Returns:
        - bool: True if a main URI was found, False otherwise
        - List[str]: List of informational messages or errors
        - Optional[str]: The main URI if found, otherwise None
    """
    messages = []

    # 1. First try to extract declared base URI from the document
    if file_path and format:
        declared_base = extract_declared_base(file_path, format)
        if declared_base:
            messages.append(f"Found declared base URI: {declared_base}")
            return True, messages, declared_base

    # 2. Fallback: check only for owl:Ontology declarations
    rdf_type = URIRef("http://www.w3.org/1999/02/22-rdf-syntax-ns#type")
    owl_ontology = URIRef("http://www.w3.org/2002/07/owl#Ontology")
    
    ontology_uris = list(g.subjects(rdf_type, owl_ontology))
    if ontology_uris:
        main_uri = str(ontology_uris[0])
        messages.append(f"Found OWL Ontology declaration: {main_uri}")
        return True, messages, main_uri

    messages.append("No main URI found to describe the graph")
    return False, messages, None


def validate_rdf_syntax(file_path: str, format: RDFFormat = RDFFormat.TURTLE) -> Tuple[bool, List[str], List[str], Optional[Graph]]:
    """Validates the syntax of an RDF file in the specified format
    
    Returns:
        Tuple containing:
        - bool: True if validation passed, False otherwise
        - List[str]: List of issues (errors that affect validation)
        - List[str]: List of warnings (non-critical issues)
        - Optional[Graph]: The parsed RDF graph if successful, None otherwise
    """
    issues = []
    warnings = []
    g = Graph()
    try:
        g.parse(file_path, format=format.value)
        # Check if there is at least one valid URI
        uri_count = 0
        for s, p, o in g:
            if isinstance(s, URIRef):
                uri_count += 1
                break

        if uri_count == 0:
            issues.append("The file does not contain valid URIs")
            return False, issues, warnings, None

        # Check if there is a main URI that describes the graph
        # We still check it, but we don't make validation depend on it
        has_main_uri, messages, main_uri = check_main_uri(g, file_path, format)
        if not has_main_uri:
            # Instead of adding to issues, we add to warnings
            warnings.extend(messages)

        return True, issues, warnings, g
    except Exception as e:
        issues.append(f"Parsing error in format {format.value}: {str(e)}")
        return False, issues, warnings, None


async def check_uri_accessibility(uri: str) -> Dict[str, Any]:
    """Checks if a URI is accessible and returns information about the MIME type"""
    if not uri.startswith(('http://', 'https://')):
        return {"accessible": False, "reason": "Not an HTTP/HTTPS URI", "mime_type": None}

    # Headers for RDF resource content negotiation
    headers = {
        'Accept': 'application/rdf+xml, text/turtle, application/n-triples, application/ld+json, text/html, */*;q=0.5'
    }

    # Uniform timeout of 10 seconds for all URIs
    timeout = 10

    # Status codes considered successful
    success_codes = [200, 203]  # OK, Non-Authoritative Information

    try:
        async with aiohttp.ClientSession() as session:
            # Execute initial GET request with allow_redirects=False to manually handle 303 redirects
            try:
                async with session.get(uri, allow_redirects=False, timeout=timeout, headers=headers) as response:
                    # If the response is a direct success, consider it accessible
                    if response.status in success_codes:
                        content_type = response.headers.get('Content-Type', '').split(';')[0].strip()
                        return {
                            "accessible": True,
                            "reason": "URI directly accessible",
                            "mime_type": content_type
                        }
                    
                    # Specific handling of 303 See Other
                    elif response.status == 303:
                        location = response.headers.get('Location')
                        if not location:
                            return {
                                "accessible": False,
                                "reason": "303 redirect without Location header",
                                "mime_type": None
                            }
                        
                        # Make a new request to the redirect URI
                        try:
                            async with session.get(location, timeout=timeout, headers=headers) as redirect_response:
                                if redirect_response.status in success_codes:
                                    content_type = redirect_response.headers.get('Content-Type', '').split(';')[0].strip()
                                    return {
                                        "accessible": True,
                                        "reason": f"URI redirects to {location} (accessible)",
                                        "mime_type": content_type
                                    }
                                else:
                                    return {
                                        "accessible": False,
                                        "reason": f"URI redirects to {location} (not accessible, status: {redirect_response.status})",
                                        "mime_type": None
                                    }
                        except Exception as redirect_error:
                            return {
                                "accessible": False,
                                "reason": f"Error following redirect to {location}: {str(redirect_error)}",
                                "mime_type": None
                            }
                    
                    # For other redirects (301, 302, 307, 308), retry with allow_redirects=True
                    elif response.status in [301, 302, 307, 308]:
                        async with session.get(uri, allow_redirects=True, timeout=timeout, headers=headers) as redirect_response:
                            if redirect_response.status in success_codes:
                                content_type = redirect_response.headers.get('Content-Type', '').split(';')[0].strip()
                                return {
                                    "accessible": True,
                                    "reason": f"URI accessible after redirect",
                                    "mime_type": content_type
                                }
                            else:
                                return {
                                    "accessible": False,
                                    "reason": f"Status code after redirect: {redirect_response.status}",
                                    "mime_type": None
                                }
                    
                    # Other status codes are considered errors
                    else:
                        return {
                            "accessible": False,
                            "reason": f"Status code: {response.status}",
                            "mime_type": None
                        }
            except asyncio.TimeoutError:
                # For RDF resources, a timeout doesn't necessarily mean the URI is invalid
                # It could be a slow server
                return {
                    "accessible": True, 
                    "reason": "Timeout during request, considered potentially accessible", 
                    "mime_type": None
                }
    except Exception as e:
        return {"accessible": False, "reason": str(e), "mime_type": None}


def check_license(g: Graph) -> Dict[str, Any]:
    """Checks for the presence of open licenses in the RDF graph"""
    from src.models import OPEN_LICENSE_DEFINITIONS
    
    # Look for common license predicates
    license_predicates = [
        DCTERMS.license,
        URIRef("http://purl.org/dc/terms/license"),
        URIRef("http://purl.org/dc/terms/rights"),
        URIRef("http://purl.org/dc/elements/1.1/license"),
        URIRef("http://purl.org/dc/elements/1.1/rights"),
        URIRef("http://www.w3.org/1999/xhtml/vocab#license"),
        URIRef("http://creativecommons.org/ns#license"),
        URIRef("http://schema.org/license"),
        URIRef("http://schema.org/licensedUnder"),
        URIRef("http://www.w3.org/ns/odrl/2/hasPolicy"),
        URIRef("http://rdfs.org/ns/void#dataDump")
    ]
    
    # Also look for license subclasses
    license_classes = [
        URIRef("http://purl.org/dc/terms/LicenseDocument"),
        URIRef("http://creativecommons.org/ns#License"),
        URIRef("http://www.w3.org/ns/odrl/2/Policy")
    ]

    licenses_found = []
    
    # 1. Look for triples with license predicates
    for pred in license_predicates:
        for subject, _, license_obj in g.triples((None, pred, None)):
            if isinstance(license_obj, URIRef):
                licenses_found.append(str(license_obj))
            
    # 2. Look for instances of license classes
    rdf_type = URIRef("http://www.w3.org/1999/02/22-rdf-syntax-ns#type")
    for license_class in license_classes:
        for license_instance, _, _ in g.triples((None, rdf_type, license_class)):
            licenses_found.append(str(license_instance))
    
    # Remove duplicates
    licenses_found = list(set(licenses_found))
    
    # Check if any of the found licenses is open (exact match with OPEN_LICENSE_DEFINITIONS)
    open_licenses = []
    for license_uri in licenses_found:
        if license_uri in OPEN_LICENSE_DEFINITIONS:
            open_licenses.append(license_uri)
    
    is_open = len(open_licenses) > 0
    
    return {
        "licenses_found": licenses_found,
        "is_open_license": is_open
    }


def analyze_external_links(g: Graph, base_domain: Optional[str] = None) -> Dict[str, Any]:
    """Analyzes external links in the RDF graph"""
    if not base_domain:
        # If no base domain is provided, give max score (all URIs are considered external)
        total_links = 0
        for _, _, o in g:
            if isinstance(o, URIRef):
                total_links += 1
        
        return {
            "external_percentage": 100.0,
            "meets_threshold": True,
            "external_count": total_links,
            "total_count": total_links,
            "note": "No base domain provided - all URIs considered external"
        }

    total_links = 0
    external_links = 0

    # Count external links in triple objects
    for _, _, o in g:
        if isinstance(o, URIRef):
            total_links += 1
            try:
                uri_domain = urllib.parse.urlparse(str(o)).netloc
                if uri_domain and uri_domain != base_domain:
                    external_links += 1
            except:
                pass

    # Calculate the percentage of external links
    external_percentage = (external_links / total_links * 100) if total_links > 0 else 0
    meets_threshold = external_percentage >= MIN_EXTERNAL_LINKS_PERCENTAGE

    return {
        "external_percentage": round(external_percentage, 2),
        "meets_threshold": meets_threshold,
        "external_count": external_links,
        "total_count": total_links
    }


async def check_uris_validity(g: Graph) -> Dict[str, Any]:
    """Verifies the validity of base URIs in the RDF graph by checking only unique base URIs"""
    all_uris = set()

    # Collect all distinct URIs in the graph
    for s, p, o in g:
        if isinstance(s, URIRef) and str(s).startswith(('http://', 'https://')):
            all_uris.add(str(s))
        if isinstance(p, URIRef) and str(p).startswith(('http://', 'https://')):
            all_uris.add(str(p))
        if isinstance(o, URIRef) and str(o).startswith(('http://', 'https://')):
            all_uris.add(str(o))

    # Extract unique base URIs (scheme + netloc)
    base_uris = set()
    
    for uri in all_uris:
        try:
            parsed = urllib.parse.urlparse(uri)
            base_uri = f"{parsed.scheme}://{parsed.netloc}"
            base_uris.add(base_uri)
        except:
            # If parsing fails, consider the full URI as base
            base_uris.add(uri)

    # Check accessibility of base URIs in parallel
    base_uris_list = list(base_uris)
    tasks = [check_uri_accessibility(base_uri) for base_uri in base_uris_list]
    results = await asyncio.gather(*tasks)

    base_uri_results = {base_uri: result for base_uri, result in zip(base_uris_list, results)}

    # Calculate metrics
    total_base_uris_found = len(base_uris_list)
    total_base_uris_accessible = sum(1 for result in base_uri_results.values() if result["accessible"])
    total_base_uris_not_accessible = total_base_uris_found - total_base_uris_accessible
    
    # List of URIs that are not accessible
    not_accessible_uris = [
        base_uri for base_uri, result in base_uri_results.items()
        if not result["accessible"]
    ]

    return {
        "total_base_uris_found": total_base_uris_found,
        "total_base_uris_accessible": total_base_uris_accessible,
        "total_base_uris_not_accessible": total_base_uris_not_accessible,
        "not_accessible_uris": not_accessible_uris
    }


def validate_with_shacl(g: Graph) -> Dict[str, Any]:
    """Validates the RDF graph using SHACL shapes against all available vocabularies"""
    # Paths to all available SHACL shapes
    shapes_paths = [
        "./shapes/skos_shapes.ttl",
        "./shapes/ontolex_shapes.ttl"
    ]
    
    validation_results = []
    overall_is_valid = True
    all_errors = []
    
    for shapes_path in shapes_paths:
        if not os.path.exists(shapes_path):
            continue
            
        try:
            shapes_graph = Graph()
            shapes_graph.parse(shapes_path, format="turtle")
            
            # Execute SHACL validation
            validation_result = pyshacl.validate(
                g,
                shacl_graph=shapes_graph,
                inference='rdfs',
                abort_on_first=False
            )
            
            conforms, results_graph, results_text = validation_result
            
            vocabulary_name = os.path.splitext(os.path.basename(shapes_path))[0].replace('_shapes', '')
            
            if conforms:
                validation_results.append({
                    "vocabulary": vocabulary_name,
                    "is_valid": True,
                    "errors": []
                })
            else:
                # Extract errors from the results graph
                error_messages = []
                for _, _, o in results_graph.triples((None, None, None)):
                    if isinstance(o, rdflib.Literal) and str(o).strip():
                        error_messages.append(str(o))
                
                validation_results.append({
                    "vocabulary": vocabulary_name,
                    "is_valid": False,
                    "errors": error_messages if error_messages else [results_text]
                })
                
                overall_is_valid = False
                all_errors.extend(error_messages if error_messages else [results_text])
                
        except Exception as e:
            vocabulary_name = os.path.splitext(os.path.basename(shapes_path))[0].replace('_shapes', '')
            error_msg = f"Error during SHACL validation for {vocabulary_name}: {str(e)}"
            validation_results.append({
                "vocabulary": vocabulary_name,
                "is_valid": False,
                "errors": [error_msg]
            })
            overall_is_valid = False
            all_errors.append(error_msg)
    
    if not validation_results:
        return {
            "is_valid": False,
            "errors": ["No SHACL shapes found"],
            "individual_results": []
        }
    
    return {
        "is_valid": overall_is_valid,
        "errors": all_errors,
        "individual_results": validation_results
    }


def calculate_quality_score(quality_report: Dict[str, Any]) -> Tuple[QualityScore, List[str]]:
    """Calculate a quality score based on the quality report (scale 0-10) and generate suggestions"""
    suggestions = []
    
    # Score for external links (0-10)
    external_links = quality_report["external_links"]
    # Calculate score from 0-10 based on percentage (10% = 10)
    external_links_score = min(10, external_links["external_percentage"]) if "external_percentage" in external_links else 0
    
    if external_links_score < 5:
        suggestions.append(f"Insufficient external links ({external_links.get('external_percentage', 0)}%). " 
                       f"Increase connections to external resources to improve interoperability.")
    
    # Score for URI validity (0-10)
    uri_validity = quality_report["uri_validity"]
    if "total_base_uris_found" in uri_validity and uri_validity["total_base_uris_found"] > 0:
        valid_percentage = (uri_validity["total_base_uris_accessible"] / uri_validity["total_base_uris_found"]) * 100
        uri_validity_score = valid_percentage / 10  # Convert percentage to 0-10 scale
        
        if uri_validity_score < 8:
            inaccessible_count = uri_validity["total_base_uris_not_accessible"]
            suggestions.append(f"Found {inaccessible_count} inaccessible base URIs out of {uri_validity['total_base_uris_found']} checked. " 
                           f"Fix invalid base URIs to improve quality.")
    else:
        uri_validity_score = 0
    
    # Score for license (0 or 10)
    license_info = quality_report["license_info"]
    license_score = 10 if license_info.get("is_open_license", False) else 0
    
    if license_score == 0:
        if not license_info.get("licenses_found"):
            suggestions.append("No license found. Add an open license to improve reusability.")
        else:
            suggestions.append("Non-open license. Use an open license to improve reusability.")
    
    # SHACL score - always calculated (0 or 10)
    shacl_score = 0
    if "shacl_validation" in quality_report and quality_report["shacl_validation"]:
        shacl_validation = quality_report["shacl_validation"]
        
        # Check if any vocabulary validation passed (max score logic)
        individual_results = shacl_validation.get("individual_results", [])
        if individual_results:
            # If any vocabulary validation passes, give max score (10)
            # If all fail, give worst score (0)
            any_valid = any(result.get("is_valid", False) for result in individual_results)
            shacl_score = 10 if any_valid else 0
        else:
            # Fallback to overall validation result
            shacl_score = 10 if shacl_validation.get("is_valid", False) else 0
        
        if shacl_score == 0 and "errors" in shacl_validation and shacl_validation["errors"]:
            suggestions.append(f"SHACL validation failed. Fix vocabulary structure errors.")
    
    # Calculate the total score (weighted average) - always including SHACL
    weights = {
        "external_links": 0.25,
        "uri_validity": 0.25,
        "license": 0.3,
        "shacl": 0.2
    }
    
    total_score = (
        external_links_score * weights["external_links"] +
        uri_validity_score * weights["uri_validity"] +
        license_score * weights["license"] +
        shacl_score * weights["shacl"]
    )
    
    return QualityScore(
        total_score=int(round(total_score)),
        external_links_score=int(round(external_links_score)),
        uri_validity_score=int(round(uri_validity_score)),
        license_score=int(round(license_score)),
        shacl_score=int(round(shacl_score))
    ), suggestions
