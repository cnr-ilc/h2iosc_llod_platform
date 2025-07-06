from enum import Enum
from pydantic import BaseModel, Field
from typing import List, Dict, Any, Optional


class RDFFormat(str, Enum):
    TURTLE = "turtle"  # .ttl
    RDFXML = "xml"  # .rdf, .owl
    NTRIPLES = "nt"  # .nt
    N3 = "n3"  # .n3
    JSONLD = "json-ld"  # .jsonld
    NQUADS = "nquads"  # .nq
    TRIG = "trig"  # .trig




class ValidationResponse(BaseModel):
    is_valid: bool = Field(description="Whether the RDF file passed syntax validation")
    issues: List[str] = Field(default=[], description="Critical errors that prevent validation")
    warnings: List[str] = Field(default=[], description="Non-critical issues (e.g., missing main URI)")
    main_uri: Optional[str] = Field(default=None, description="The main URI describing the resource (if found)")


class QualityReport(BaseModel):
    external_links: Dict[str, Any] = Field(description="External links analysis results including percentage and counts")
    uri_validity: Dict[str, Any] = Field(description="Base URI validation results with accessibility statistics and list of inaccessible URIs")
    license_info: Dict[str, Any] = Field(description="License detection results including found licenses and open license status")
    shacl_validation: Dict[str, Any] = Field(description="SHACL validation results against all available vocabulary shapes")
    suggestions: List[str] = Field(default=[], description="Actionable recommendations for improving resource quality")


class QualityScore(BaseModel):
    total_score: int = Field(description="Overall quality score (0-10) - weighted average of all components", ge=0, le=10)
    external_links_score: int = Field(description="External links score (0-10) based on percentage of external connections", ge=0, le=10)
    uri_validity_score: int = Field(description="URI validity score (0-10) based on accessibility of unique base URIs", ge=0, le=10)
    license_score: int = Field(description="License score (0 or 10) - 10 if open license found, 0 otherwise", ge=0, le=10)
    shacl_score: int = Field(description="SHACL validation score (0 or 10) - always present", ge=0, le=10)


class QualityResponse(BaseModel):
    score: QualityScore = Field(description="Quality scores for each dimension (all integers 0-10)")
    report: QualityReport = Field(description="Detailed quality analysis results and suggestions")


class QualityCriteria(Enum):
    HIGH = "high"
    MEDIUM = "medium"
    LOW = "low"


# Mapping of file extensions to RDF formats
EXTENSION_TO_FORMAT = {
    '.ttl': RDFFormat.TURTLE,
    '.rdf': RDFFormat.RDFXML,
    '.owl': RDFFormat.RDFXML,
    '.nt': RDFFormat.NTRIPLES,
    '.n3': RDFFormat.N3,
    '.jsonld': RDFFormat.JSONLD,
    '.json': RDFFormat.JSONLD,  # Assume that .json could be JSON-LD
    '.nq': RDFFormat.NQUADS,
    '.trig': RDFFormat.TRIG
}

# Constants
MIN_EXTERNAL_LINKS_PERCENTAGE = 10

# Comprehensive list of recognized open licenses
OPEN_LICENSE_DEFINITIONS = {
    # Creative Commons
    "http://creativecommons.org/licenses/by/": "Creative Commons Attribution",
    "http://creativecommons.org/licenses/by-sa/": "Creative Commons Attribution-ShareAlike",
    "http://creativecommons.org/licenses/by-nc/": "Creative Commons Attribution-NonCommercial",
    "http://creativecommons.org/licenses/by-nc-sa/": "Creative Commons Attribution-NonCommercial-ShareAlike",
    "http://creativecommons.org/licenses/by-nd/": "Creative Commons Attribution-NoDerivs",
    "http://creativecommons.org/licenses/by-nc-nd/": "Creative Commons Attribution-NonCommercial-NoDerivs",
    "http://creativecommons.org/publicdomain/zero/": "Creative Commons CC0 (Public Domain)",
    "http://creativecommons.org/publicdomain/mark/": "Creative Commons Public Domain Mark",
    
    # Open Data Commons
    "http://opendatacommons.org/licenses/pddl/": "ODC Public Domain Dedication and License",
    "http://opendatacommons.org/licenses/by/": "ODC Attribution License",
    "http://opendatacommons.org/licenses/odbl/": "ODC Open Database License",
    
    # GNU/FSF Licenses
    "http://www.gnu.org/licenses/gpl": "GNU General Public License",
    "http://www.gnu.org/licenses/agpl": "GNU Affero General Public License",
    "http://www.gnu.org/licenses/lgpl": "GNU Lesser General Public License",
    "http://www.gnu.org/licenses/fdl": "GNU Free Documentation License",
    
    # Altri formati comuni
    "http://opensource.org/licenses/MIT": "MIT License",
    "http://opensource.org/licenses/Apache-2.0": "Apache License 2.0",
    "http://opensource.org/licenses/BSD": "BSD License",
    "http://opensource.org/licenses/MPL": "Mozilla Public License",
    "http://www.w3.org/Consortium/Legal/copyright-software": "W3C Software License",
    "http://www.w3.org/Consortium/Legal/2015/copyright-software-and-document": "W3C Software and Document License",
    
    # HTTPS varianti delle licenze CC
    "https://creativecommons.org/licenses/by/": "Creative Commons Attribution",
    "https://creativecommons.org/licenses/by-sa/": "Creative Commons Attribution-ShareAlike",
    "https://creativecommons.org/licenses/by-nc/": "Creative Commons Attribution-NonCommercial",
    "https://creativecommons.org/licenses/by-nc-sa/": "Creative Commons Attribution-NonCommercial-ShareAlike",
    "https://creativecommons.org/licenses/by-nd/": "Creative Commons Attribution-NoDerivs",
    "https://creativecommons.org/licenses/by-nc-nd/": "Creative Commons Attribution-NonCommercial-NoDerivs",
    "https://creativecommons.org/publicdomain/zero/": "Creative Commons CC0 (Public Domain)",
    "https://creativecommons.org/publicdomain/mark/": "Creative Commons Public Domain Mark",
    
    # URI con parole chiave di licenza aperta
    "http://unlicense.org": "The Unlicense",
    "https://unlicense.org": "The Unlicense",
    
    # Specifici URI per versioni di CC
    "http://creativecommons.org/licenses/by/4.0/": "Creative Commons Attribution 4.0",
    "http://creativecommons.org/licenses/by-sa/4.0/": "Creative Commons Attribution-ShareAlike 4.0",
    "http://creativecommons.org/licenses/by-nc/4.0/": "Creative Commons Attribution-NonCommercial 4.0",
    "http://creativecommons.org/licenses/by-nc-sa/4.0/": "Creative Commons Attribution-NonCommercial-ShareAlike 4.0",
    "http://creativecommons.org/licenses/by-nd/4.0/": "Creative Commons Attribution-NoDerivs 4.0",
    "http://creativecommons.org/licenses/by-nc-nd/4.0/": "Creative Commons Attribution-NonCommercial-NoDerivs 4.0",
    "https://creativecommons.org/licenses/by/4.0/": "Creative Commons Attribution 4.0",
    "https://creativecommons.org/licenses/by-sa/4.0/": "Creative Commons Attribution-ShareAlike 4.0",
    "https://creativecommons.org/licenses/by-nc/4.0/": "Creative Commons Attribution-NonCommercial 4.0",
    "https://creativecommons.org/licenses/by-nc-sa/4.0/": "Creative Commons Attribution-NonCommercial-ShareAlike 4.0",
    "https://creativecommons.org/licenses/by-nd/4.0/": "Creative Commons Attribution-NoDerivs 4.0",
    "https://creativecommons.org/licenses/by-nc-nd/4.0/": "Creative Commons Attribution-NonCommercial-NoDerivs 4.0",
    
    # Wikidata IDs per licenze note (sia form wiki che entity)
    "https://www.wikidata.org/wiki/Q6938433": "Creative Commons Attribution",
    "https://www.wikidata.org/wiki/Q24082749": "Creative Commons Attribution-ShareAlike 4.0",
    "https://www.wikidata.org/wiki/Q42553662": "Creative Commons Attribution-NonCommercial-ShareAlike 4.0", 
    "https://www.wikidata.org/wiki/Q6939439": "Creative Commons Attribution-ShareAlike",
    "https://www.wikidata.org/wiki/Q6905942": "Creative Commons Attribution-NonCommercial",
    "https://www.wikidata.org/wiki/Q6905323": "Creative Commons CC0",
    
    "http://www.wikidata.org/entity/Q6938433": "Creative Commons Attribution",
    "http://www.wikidata.org/entity/Q24082749": "Creative Commons Attribution-ShareAlike 4.0",
    "http://www.wikidata.org/entity/Q42553662": "Creative Commons Attribution-NonCommercial-ShareAlike 4.0", 
    "http://www.wikidata.org/entity/Q6939439": "Creative Commons Attribution-ShareAlike",
    "http://www.wikidata.org/entity/Q6905942": "Creative Commons Attribution-NonCommercial",
    "http://www.wikidata.org/entity/Q6905323": "Creative Commons CC0",
    
    "https://www.wikidata.org/entity/Q6938433": "Creative Commons Attribution",
    "https://www.wikidata.org/entity/Q24082749": "Creative Commons Attribution-ShareAlike 4.0",
    "https://www.wikidata.org/entity/Q42553662": "Creative Commons Attribution-NonCommercial-ShareAlike 4.0", 
    "https://www.wikidata.org/entity/Q6939439": "Creative Commons Attribution-ShareAlike",
    "https://www.wikidata.org/entity/Q6905942": "Creative Commons Attribution-NonCommercial",
    "https://www.wikidata.org/entity/Q6905323": "Creative Commons CC0",
    
    "http://wikidata.org/entity/Q6938433": "Creative Commons Attribution",
    "http://wikidata.org/entity/Q24082749": "Creative Commons Attribution-ShareAlike 4.0",
    "http://wikidata.org/entity/Q42553662": "Creative Commons Attribution-NonCommercial-ShareAlike 4.0", 
    "http://wikidata.org/entity/Q6939439": "Creative Commons Attribution-ShareAlike",
    "http://wikidata.org/entity/Q6905942": "Creative Commons Attribution-NonCommercial",
    "http://wikidata.org/entity/Q6905323": "Creative Commons CC0",
    
    # URI per formati di licenza di DBpedia e altre risorse
    "http://dbpedia.org/resource/Creative_Commons_license": "Creative Commons",
    "http://dbpedia.org/resource/Creative_Commons": "Creative Commons",
    "http://dbpedia.org/resource/Public_domain": "Public Domain",
    "http://dbpedia.org/resource/MIT_License": "MIT License"
}
