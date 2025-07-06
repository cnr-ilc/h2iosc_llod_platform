from fastapi import FastAPI, UploadFile, File, Form, HTTPException, BackgroundTasks, Depends
from fastapi.responses import JSONResponse
from typing import Optional, Union
import os

from src.models import RDFFormat, ValidationResponse, QualityResponse, QualityReport
from src.rdf_utils import (
    save_upload_file_tmp, get_rdf_format_from_filename, validate_rdf_syntax,
    check_main_uri, check_license, analyze_external_links, check_uris_validity,
    validate_with_shacl, calculate_quality_score
)

app = FastAPI(
    title="PLLOD RDF Validation and Quality Assessment API",
    version="1.0.0"
)




# Endpoints
@app.get("/")
async def root():
    """Welcome endpoint for the API"""
    return {"message": "PLLOD Resource File Validation API is running"}


@app.post("/health", 
          response_model=ValidationResponse,
          summary="Validate RDF Syntax",
          responses={
              200: {
                  "description": "Validation completed successfully",
                  "content": {
                      "application/json": {
                          "examples": {
                              "valid_with_base": {
                                  "summary": "Valid RDF with declared base URI",
                                  "value": {
                                      "is_valid": True,
                                      "issues": [],
                                      "warnings": [],
                                      "main_uri": "http://example.org/ontology/"
                                  }
                              },
                              "valid_with_ontology": {
                                  "summary": "Valid RDF with owl:Ontology",
                                  "value": {
                                      "is_valid": True,
                                      "issues": [],
                                      "warnings": [],
                                      "main_uri": "http://example.org/animals"
                                  }
                              },
                              "valid_no_main_uri": {
                                  "summary": "Valid RDF without main URI",
                                  "value": {
                                      "is_valid": True,
                                      "issues": [],
                                      "warnings": ["No main URI found to describe the graph"],
                                      "main_uri": None
                                  }
                              },
                              "invalid_rdf": {
                                  "summary": "Invalid RDF syntax",
                                  "value": {
                                      "is_valid": False,
                                      "issues": ["Parsing error in format turtle: ..."],
                                      "warnings": [],
                                      "main_uri": None
                                  }
                              }
                          }
                      }
                  }
              }
          })
async def validate_health(
        file: UploadFile = File(..., description="The RDF file to validate for syntax correctness"),
        format: Optional[RDFFormat] = Form(None, description="RDF format (turtle, xml, etc.). Auto-detected from file extension if not specified")
):
    """
    Validate the syntax and basic structure of an RDF file.
    
    This endpoint performs basic validation to ensure the file:
    - Has valid RDF syntax in the specified/detected format
    - Contains at least one valid URI
    - Can be successfully parsed
    
    **Parameters:**
    - **file**: RDF file to validate
    - **format**: RDF serialization format (auto-detected if not specified)
    
    **Returns:**
    - **is_valid**: Boolean indicating if the file passed validation
    - **issues**: List of critical errors that prevent validation
    - **warnings**: List of non-critical issues (e.g., missing main URI declaration)
    - **main_uri**: The main URI describing the resource (if found)
    
    **Note**: This endpoint only checks syntax and basic structure. 
    For comprehensive quality analysis, use the `/quality` endpoint.
    """
    tmp_path = save_upload_file_tmp(file)
    try:
        # Determine the format if not specified or empty
        if format is None or format == "":
            format = get_rdf_format_from_filename(file.filename)

        is_valid, issues, warnings, g = validate_rdf_syntax(tmp_path, format)

        main_uri = None
        if g:
            # We still try to get the main URI, but validation doesn't depend on it
            has_main_uri, _, uri = check_main_uri(g, tmp_path, format)
            if has_main_uri:
                main_uri = uri

        return ValidationResponse(is_valid=is_valid, issues=issues, warnings=warnings, main_uri=main_uri)
    finally:
        os.unlink(tmp_path)


@app.post("/quality", 
          response_model=QualityResponse,
          summary="Analyze RDF Quality",
          responses={
              200: {
                  "description": "Quality analysis completed successfully",
                  "content": {
                      "application/json": {
                          "examples": {
                              "high_quality": {
                                  "summary": "High quality RDF with good scores",
                                  "value": {
                                      "score": {
                                          "total_score": 8,
                                          "external_links_score": 7,
                                          "uri_validity_score": 9,
                                          "license_score": 10,
                                          "shacl_score": 10
                                      },
                                      "report": {
                                          "external_links": {
                                              "external_percentage": 35.5,
                                              "meets_threshold": True,
                                              "external_count": 15,
                                              "total_count": 42
                                          },
                                          "uri_validity": {
                                              "total_base_uris_found": 10,
                                              "total_base_uris_accessible": 9,
                                              "total_base_uris_not_accessible": 1,
                                              "not_accessible_uris": ["http://broken.example.org"]
                                          },
                                          "license_info": {
                                              "licenses_found": ["https://creativecommons.org/licenses/by/4.0/"],
                                              "is_open_license": True
                                          },
                                          "shacl_validation": {
                                              "is_valid": True,
                                              "errors": [],
                                              "individual_results": [
                                                  {
                                                      "vocabulary": "skos",
                                                      "is_valid": True,
                                                      "errors": []
                                                  },
                                                  {
                                                      "vocabulary": "ontolex",
                                                      "is_valid": True,
                                                      "errors": []
                                                  }
                                              ]
                                          },
                                          "suggestions": []
                                      }
                                  }
                              },
                              "no_base_domain": {
                                  "summary": "Quality analysis without base domain (100% external)",
                                  "value": {
                                      "score": {
                                          "total_score": 9,
                                          "external_links_score": 10,
                                          "uri_validity_score": 8,
                                          "license_score": 10,
                                          "shacl_score": 0
                                      },
                                      "report": {
                                          "external_links": {
                                              "external_percentage": 100.0,
                                              "meets_threshold": True,
                                              "external_count": 42,
                                              "total_count": 42,
                                              "note": "No base domain provided - all URIs considered external"
                                          },
                                          "uri_validity": {
                                              "total_base_uris_found": 5,
                                              "total_base_uris_accessible": 4,
                                              "total_base_uris_not_accessible": 1,
                                              "not_accessible_uris": ["http://timeout.example.org"]
                                          },
                                          "license_info": {
                                              "licenses_found": ["https://creativecommons.org/licenses/by-sa/4.0/"],
                                              "is_open_license": True
                                          },
                                          "shacl_validation": {
                                              "is_valid": False,
                                              "errors": ["SHACL validation failed"],
                                              "individual_results": [
                                                  {
                                                      "vocabulary": "skos",
                                                      "is_valid": False,
                                                      "errors": ["Concept without prefLabel"]
                                                  },
                                                  {
                                                      "vocabulary": "ontolex",
                                                      "is_valid": False,
                                                      "errors": ["Missing lexical form"]
                                                  }
                                              ]
                                          },
                                          "suggestions": ["SHACL validation failed. Fix vocabulary structure errors."]
                                      }
                                  }
                              }
                          }
                      }
                  }
              },
              400: {
                  "description": "Invalid RDF file",
                  "content": {
                      "application/json": {
                          "examples": {
                              "invalid_syntax": {
                                  "summary": "Invalid RDF syntax error",
                                  "value": {
                                      "error": "The file is not valid in format turtle",
                                      "details": ["Parsing error in format turtle: ..."]
                                  }
                              }
                          }
                      }
                  }
              }
          })
async def analyze_quality(
        file: UploadFile = File(..., description="The RDF file to analyze for quality metrics"),
        format: Optional[RDFFormat] = Form(None, description="RDF format (turtle, xml, etc.). Auto-detected from file extension if not specified"),
        base_domain: Optional[str] = Form(None, description="Base domain for external links analysis (e.g., 'example.org'). If not provided, all URIs are considered external (100% score)")
):
    """
    Analyze the quality of an RDF file and return detailed quality metrics.
    
    This endpoint performs comprehensive quality analysis including:
    - **External Links Analysis**: Measures connectivity to external resources (0-10 score)
    - **URI Validity Check**: Validates accessibility of unique base URIs (0-10 score) 
    - **License Detection**: Checks for presence of open licenses (0 or 10 score)
    - **SHACL Validation**: Validates against all available vocabulary shapes (0 or 10 score, always performed)
    
    **Scoring System**: All scores are integers from 0-10 for easy interpretation.
    
    **Parameters:**
    - **file**: RDF file in supported format (turtle, rdf/xml, n-triples, json-ld, etc.)
    - **format**: Explicitly specify RDF format if auto-detection fails
    - **base_domain**: Domain to use for external links calculation. If not provided, all URIs are considered external (giving maximum external links score)
    
    **Returns:**
    - **score**: Quality scores for each dimension plus total weighted score
    - **report**: Detailed analysis results including:
      - external_links: Percentage and count of external connections
      - uri_validity: Base URI accessibility statistics and list of inaccessible URIs
      - license_info: License detection results
      - shacl_validation: Vocabulary compliance results (always included)
      - suggestions: Actionable recommendations for quality improvement
    
    **URI Validation**: Only unique base URIs (scheme + domain) are checked for accessibility,
    making the validation more efficient and focused on actual server availability.
    """
    tmp_path = save_upload_file_tmp(file)
    try:
        # Determine the format if not specified or empty
        if format is None or format == "":
            format = get_rdf_format_from_filename(file.filename)

        # Valida prima la sintassi
        is_valid, issues, warnings, g = validate_rdf_syntax(tmp_path, format)
        if not is_valid:
            return JSONResponse(
                status_code=400,
                content={"error": f"The file is not valid in format {format.value}", "details": issues}
            )

        # Run quality analyses
        external_links = analyze_external_links(g, base_domain)
        license_info = check_license(g)
        uri_validity = await check_uris_validity(g)

        report = {
            "external_links": external_links,
            "uri_validity": uri_validity,
            "license_info": license_info
        }

        shacl_result = validate_with_shacl(g)
        report["shacl_validation"] = shacl_result

        quality_score, suggestions = calculate_quality_score(report)
        
        report["suggestions"] = suggestions
        
        return QualityResponse(
            score=quality_score,
            report=QualityReport(**report)
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error during analysis: {str(e)}")
    finally:
        os.unlink(tmp_path)  # Remove the temporary file


@app.get("/supported-vocabularies",
         summary="Get Supported SHACL Vocabularies",
         responses={
             200: {
                 "description": "List of supported vocabularies",
                 "content": {
                     "application/json": {
                         "example": {
                             "vocabularies": ["skos", "ontolex"],
                             "description": "Supported vocabularies for SHACL validation. All shapes are automatically checked during quality analysis."
                         }
                     }
                 }
             }
         })
async def get_supported_vocabularies():
    """
    Get the list of supported vocabulary types for SHACL validation.
    
    SHACL validation is always performed against all available vocabulary shapes
    in the `/quality` endpoint.
    
    **Available vocabularies:**
    - **skos**: Simple Knowledge Organization System (SKOS) - comprehensive validation including official W3C constraints and quality checks
    - **ontolex**: OntoLex vocabulary for lexical resources
    
    **Returns:**
    - **vocabularies**: List of supported vocabulary shapes that are always checked
    - **description**: Explanation of supported vocabularies
    """
    return {
        "vocabularies": ["skos", "ontolex"],
        "description": "Supported vocabularies for SHACL validation. All shapes are automatically checked during quality analysis."
    }


@app.get("/supported-formats",
         summary="Get Supported RDF Formats",
         responses={
             200: {
                 "description": "List of supported RDF formats",
                 "content": {
                     "application/json": {
                         "example": {
                             "formats": ["turtle", "xml", "nt", "n3", "json-ld", "nquads", "trig"],
                             "extension_mapping": {
                                 ".ttl": "turtle",
                                 ".rdf": "xml",
                                 ".owl": "xml",
                                 ".nt": "nt",
                                 ".n3": "n3",
                                 ".jsonld": "json-ld",
                                 ".json": "json-ld",
                                 ".nq": "nquads",
                                 ".trig": "trig"
                             },
                             "description": "Supported RDF serialization formats with automatic format detection based on file extensions"
                         }
                     }
                 }
             }
         })
async def get_supported_formats():
    """
    Get the list of supported RDF serialization formats.
    
    Returns all RDF formats that can be processed by the API, along with
    the file extension mappings used for auto-detection.
    
    **Supported formats:**
    - **turtle**: Terse RDF Triple Language (.ttl)
    - **xml**: RDF/XML (.rdf, .owl)
    - **nt**: N-Triples (.nt)
    - **n3**: Notation3 (.n3)
    - **json-ld**: JSON-LD (.jsonld, .json)
    - **nquads**: N-Quads (.nq)
    - **trig**: TriG (.trig)
    
    **Returns:**
    - **formats**: List of supported format identifiers
    - **extension_mapping**: Mapping of file extensions to format types
    - **description**: Explanation of supported formats
    """
    from src.models import EXTENSION_TO_FORMAT
    return {
        "formats": [e.value for e in RDFFormat],
        "extension_mapping": {ext: format.value for ext, format in EXTENSION_TO_FORMAT.items()},
        "description": "Supported RDF serialization formats with automatic format detection based on file extensions"
    }