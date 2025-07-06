"""
Tests for the /health endpoint - RDF syntax validation and main URI detection
"""

import pytest
from tests.conftest import (
    TURTLE_WITH_BASE, TURTLE_WITH_OWL_ONTOLOGY, TURTLE_NO_BASE_NO_ONTOLOGY,
    RDFXML_WITH_BASE, JSONLD_WITH_BASE, INVALID_RDF
)


class TestHealthEndpoint:
    """Tests for /health endpoint"""
    
    def test_health_turtle_with_base(self, client, temp_file):
        """Test Turtle file with @base declaration"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert len(data["issues"]) == 0
        assert len(data["warnings"]) == 0  # Should not warn since base is found
        assert data["main_uri"] == "http://example.org/ontology/"
    
    def test_health_turtle_with_owl_ontology(self, client, temp_file):
        """Test Turtle file with owl:Ontology declaration"""
        file_path = temp_file(TURTLE_WITH_OWL_ONTOLOGY, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert len(data["issues"]) == 0
        assert len(data["warnings"]) == 0  # Should not warn since ontology is found
        assert data["main_uri"] == "http://example.org/animals"
    
    def test_health_turtle_no_base_no_ontology(self, client, temp_file):
        """Test Turtle file without base or ontology"""
        file_path = temp_file(TURTLE_NO_BASE_NO_ONTOLOGY, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert len(data["issues"]) == 0
        assert len(data["warnings"]) == 1
        assert "No main URI found" in data["warnings"][0]
        assert data["main_uri"] is None
    
    def test_health_rdfxml_with_base(self, client, temp_file):
        """Test RDF/XML file with xml:base"""
        file_path = temp_file(RDFXML_WITH_BASE, ".rdf")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.rdf", f, "application/rdf+xml")})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert len(data["issues"]) == 0
        assert len(data["warnings"]) == 0
        assert data["main_uri"] == "http://example.org/ontology/"
    
    def test_health_jsonld_with_base(self, client, temp_file):
        """Test JSON-LD file with @base"""
        file_path = temp_file(JSONLD_WITH_BASE, ".jsonld")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.jsonld", f, "application/ld+json")})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert len(data["issues"]) == 0
        assert len(data["warnings"]) == 0
        assert data["main_uri"] == "http://example.org/ontology/"
    
    def test_health_invalid_rdf(self, client, temp_file):
        """Test invalid RDF file"""
        file_path = temp_file(INVALID_RDF, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is False
        assert len(data["issues"]) > 0
        assert data["main_uri"] is None
    
    def test_health_format_autodetection(self, client, temp_file):
        """Test format auto-detection from filename"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            # Don't specify format - should auto-detect from .ttl extension
            response = client.post("/health", files={"file": ("test.ttl", f)})
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert data["main_uri"] == "http://example.org/ontology/"
    
    def test_health_explicit_format_override(self, client, temp_file):
        """Test explicit format parameter overrides filename detection"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post(
                "/health", 
                files={"file": ("test.ttl", f)},
                data={"format": "turtle"}
            )
        
        assert response.status_code == 200
        data = response.json()
        assert data["is_valid"] is True
        assert data["main_uri"] == "http://example.org/ontology/"
    
    def test_health_response_structure(self, client, temp_file):
        """Test that response has correct structure"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/health", files={"file": ("test.ttl", f)})
        
        assert response.status_code == 200
        data = response.json()
        
        # Check required fields
        assert "is_valid" in data
        assert "issues" in data
        assert "warnings" in data
        assert "main_uri" in data
        
        # Check types
        assert isinstance(data["is_valid"], bool)
        assert isinstance(data["issues"], list)
        assert isinstance(data["warnings"], list)
        assert data["main_uri"] is None or isinstance(data["main_uri"], str)