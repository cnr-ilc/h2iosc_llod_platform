"""
Pytest configuration and shared fixtures for RDF Validation API tests
"""

import pytest
import tempfile
import os
from fastapi.testclient import TestClient
from src.main import app


@pytest.fixture
def client():
    """FastAPI test client fixture"""
    return TestClient(app)


@pytest.fixture
def temp_file():
    """Fixture that provides a function to create temporary files"""
    created_files = []
    
    def _create_temp_file(content: str, suffix: str = ".ttl") -> str:
        """Create a temporary file with content"""
        with tempfile.NamedTemporaryFile(mode='w', suffix=suffix, delete=False, encoding='utf-8') as f:
            f.write(content)
            created_files.append(f.name)
            return f.name
    
    yield _create_temp_file
    
    # Cleanup after test
    for file_path in created_files:
        try:
            os.unlink(file_path)
        except:
            pass


# Common test data
TURTLE_WITH_BASE = """
@base <http://example.org/ontology/> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

<concept1> a skos:Concept ;
    skos:prefLabel "Animal" ;
    skos:broader <http://dbpedia.org/resource/LivingThing> .

<concept2> a skos:Concept ;
    skos:prefLabel "Dog" ;
    skos:broader <concept1> .
"""

TURTLE_WITH_OWL_ONTOLOGY = """
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

<http://example.org/animals> a owl:Ontology ;
    rdfs:label "Animal Taxonomy" .

<http://example.org/concept1> a skos:Concept ;
    skos:prefLabel "Animal" .
"""

TURTLE_NO_BASE_NO_ONTOLOGY = """
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

<http://example.org/concept1> a skos:Concept ;
    skos:prefLabel "Animal" ;
    skos:broader <http://dbpedia.org/resource/LivingThing> .
"""

RDFXML_WITH_BASE = """<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:skos="http://www.w3.org/2004/02/skos/core#"
         xml:base="http://example.org/ontology/">
    
    <skos:Concept rdf:about="concept1">
        <skos:prefLabel>Animal</skos:prefLabel>
        <skos:broader rdf:resource="http://dbpedia.org/resource/LivingThing"/>
    </skos:Concept>
</rdf:RDF>"""

JSONLD_WITH_BASE = """{
    "@context": {
        "@base": "http://example.org/ontology/",
        "skos": "http://www.w3.org/2004/02/skos/core#"
    },
    "@graph": [
        {
            "@id": "concept1",
            "@type": "skos:Concept",
            "skos:prefLabel": "Animal"
        }
    ]
}"""

INVALID_RDF = """
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .

<concept1> skos:prefLabel "Missing 'a' statement .
"""

TURTLE_WITH_LICENSE = """
@base <http://example.org/ontology/> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix dct: <http://purl.org/dc/terms/> .

<> a skos:ConceptScheme ;
    dct:license <https://creativecommons.org/licenses/by/4.0/> ;
    skos:prefLabel "Animal Taxonomy" .

<concept1> a skos:Concept ;
    skos:prefLabel "Animal" .
"""