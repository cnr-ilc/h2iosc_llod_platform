"""
Tests for base URI extraction functionality - declared base and owl:Ontology detection
"""

import pytest
from src.rdf_utils import extract_declared_base, check_main_uri
from src.models import RDFFormat
from rdflib import Graph


class TestBaseUriExtraction:
    """Tests for base URI extraction functions"""
    
    def test_extract_declared_base_turtle(self, temp_file):
        """Test extracting @base from Turtle files"""
        content = """
        @base <http://example.org/ontology/> .
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <concept1> a skos:Concept .
        """
        file_path = temp_file(content, ".ttl")
        
        result = extract_declared_base(file_path, RDFFormat.TURTLE)
        assert result == "http://example.org/ontology/"
    
    def test_extract_declared_base_turtle_no_base(self, temp_file):
        """Test Turtle file without @base declaration"""
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <http://example.org/concept1> a skos:Concept .
        """
        file_path = temp_file(content, ".ttl")
        
        result = extract_declared_base(file_path, RDFFormat.TURTLE)
        assert result is None
    
    def test_extract_declared_base_rdfxml(self, temp_file):
        """Test extracting xml:base from RDF/XML files"""
        content = """<?xml version="1.0"?>
        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
                 xmlns:skos="http://www.w3.org/2004/02/skos/core#"
                 xml:base="http://example.org/ontology/">
            
            <skos:Concept rdf:about="concept1"/>
        </rdf:RDF>"""
        file_path = temp_file(content, ".rdf")
        
        result = extract_declared_base(file_path, RDFFormat.RDFXML)
        assert result == "http://example.org/ontology/"
    
    def test_extract_declared_base_rdfxml_no_base(self, temp_file):
        """Test RDF/XML file without xml:base"""
        content = """<?xml version="1.0"?>
        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
                 xmlns:skos="http://www.w3.org/2004/02/skos/core#">
            
            <skos:Concept rdf:about="http://example.org/concept1"/>
        </rdf:RDF>"""
        file_path = temp_file(content, ".rdf")
        
        result = extract_declared_base(file_path, RDFFormat.RDFXML)
        assert result is None
    
    def test_extract_declared_base_jsonld(self, temp_file):
        """Test extracting @base from JSON-LD files"""
        content = """{
            "@context": {
                "@base": "http://example.org/ontology/",
                "skos": "http://www.w3.org/2004/02/skos/core#"
            },
            "@graph": [
                {
                    "@id": "concept1",
                    "@type": "skos:Concept"
                }
            ]
        }"""
        file_path = temp_file(content, ".jsonld")
        
        result = extract_declared_base(file_path, RDFFormat.JSONLD)
        assert result == "http://example.org/ontology/"
    
    def test_extract_declared_base_jsonld_root_level(self, temp_file):
        """Test extracting @base from JSON-LD at root level"""
        content = """{
            "@base": "http://example.org/ontology/",
            "@context": {
                "skos": "http://www.w3.org/2004/02/skos/core#"
            },
            "@id": "concept1",
            "@type": "skos:Concept"
        }"""
        file_path = temp_file(content, ".jsonld")
        
        result = extract_declared_base(file_path, RDFFormat.JSONLD)
        assert result == "http://example.org/ontology/"
    
    def test_extract_declared_base_jsonld_no_base(self, temp_file):
        """Test JSON-LD file without @base"""
        content = """{
            "@context": {
                "skos": "http://www.w3.org/2004/02/skos/core#"
            },
            "@id": "http://example.org/concept1",
            "@type": "skos:Concept"
        }"""
        file_path = temp_file(content, ".jsonld")
        
        result = extract_declared_base(file_path, RDFFormat.JSONLD)
        assert result is None
    
    def test_extract_declared_base_unsupported_format(self, temp_file):
        """Test unsupported format returns None"""
        content = "<http://example.org/s> <http://example.org/p> <http://example.org/o> ."
        file_path = temp_file(content, ".nt")
        
        result = extract_declared_base(file_path, RDFFormat.NTRIPLES)
        assert result is None
    
    def test_check_main_uri_with_declared_base(self, temp_file):
        """Test check_main_uri prioritizes declared base"""
        content = """
        @base <http://example.org/ontology/> .
        @prefix owl: <http://www.w3.org/2002/07/owl#> .
        
        <http://another.org/ontology> a owl:Ontology .
        <concept1> a owl:Class .
        """
        file_path = temp_file(content, ".ttl")
        
        # Parse into graph
        g = Graph()
        g.parse(file_path, format="turtle")
        
        has_main_uri, messages, main_uri = check_main_uri(g, file_path, RDFFormat.TURTLE)
        
        assert has_main_uri is True
        assert main_uri == "http://example.org/ontology/"
        assert any("declared base" in msg.lower() for msg in messages)
    
    def test_check_main_uri_fallback_to_ontology(self, temp_file):
        """Test check_main_uri falls back to owl:Ontology when no declared base"""
        content = """
        @prefix owl: <http://www.w3.org/2002/07/owl#> .
        
        <http://example.org/ontology> a owl:Ontology .
        <http://example.org/concept1> a owl:Class .
        """
        file_path = temp_file(content, ".ttl")
        
        # Parse into graph
        g = Graph()
        g.parse(file_path, format="turtle")
        
        has_main_uri, messages, main_uri = check_main_uri(g, file_path, RDFFormat.TURTLE)
        
        assert has_main_uri is True
        assert main_uri == "http://example.org/ontology"
        assert any("owl ontology" in msg.lower() for msg in messages)
    
    def test_check_main_uri_no_base_no_ontology(self, temp_file):
        """Test check_main_uri returns None when neither declared base nor owl:Ontology found"""
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <http://example.org/concept1> a skos:Concept .
        """
        file_path = temp_file(content, ".ttl")
        
        # Parse into graph
        g = Graph()
        g.parse(file_path, format="turtle")
        
        has_main_uri, messages, main_uri = check_main_uri(g, file_path, RDFFormat.TURTLE)
        
        assert has_main_uri is False
        assert main_uri is None
        assert any("no main uri found" in msg.lower() for msg in messages)
    
    def test_check_main_uri_without_file_params(self, temp_file):
        """Test check_main_uri works with graph-only (backward compatibility)"""
        content = """
        @prefix owl: <http://www.w3.org/2002/07/owl#> .
        
        <http://example.org/ontology> a owl:Ontology .
        """
        file_path = temp_file(content, ".ttl")
        
        # Parse into graph
        g = Graph()
        g.parse(file_path, format="turtle")
        
        # Call without file parameters
        has_main_uri, messages, main_uri = check_main_uri(g)
        
        assert has_main_uri is True
        assert main_uri == "http://example.org/ontology"
    
    def test_extract_declared_base_case_insensitive(self, temp_file):
        """Test that base extraction is case insensitive"""
        content = """
        @BASE <http://example.org/ontology/> .
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <concept1> a skos:Concept .
        """
        file_path = temp_file(content, ".ttl")
        
        result = extract_declared_base(file_path, RDFFormat.TURTLE)
        assert result == "http://example.org/ontology/"
    
    def test_extract_declared_base_with_whitespace(self, temp_file):
        """Test that base extraction handles various whitespace"""
        content = """
        @base    <http://example.org/ontology/>    .
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <concept1> a skos:Concept .
        """
        file_path = temp_file(content, ".ttl")
        
        result = extract_declared_base(file_path, RDFFormat.TURTLE)
        assert result == "http://example.org/ontology/"