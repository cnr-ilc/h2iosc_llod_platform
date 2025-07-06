"""
Tests for support endpoints - formats, vocabularies, and root endpoint
"""

import pytest


class TestSupportEndpoints:
    """Tests for supported formats and vocabularies endpoints"""
    
    def test_supported_formats(self, client):
        """Test /supported-formats endpoint"""
        response = client.get("/supported-formats")
        assert response.status_code == 200
        data = response.json()
        
        # Check required fields
        assert "formats" in data
        assert "extension_mapping" in data
        assert "description" in data
        
        # Check formats list
        formats = data["formats"]
        assert isinstance(formats, list)
        assert len(formats) > 0
        
        # Check some expected formats
        expected_formats = ["turtle", "xml", "json-ld", "nt", "n3", "nquads", "trig"]
        for fmt in expected_formats:
            assert fmt in formats
        
        # Check extension mapping
        extension_mapping = data["extension_mapping"]
        assert isinstance(extension_mapping, dict)
        assert len(extension_mapping) > 0
        
        # Check some expected mappings
        expected_mappings = {
            ".ttl": "turtle",
            ".rdf": "xml",
            ".owl": "xml",
            ".jsonld": "json-ld",
            ".nt": "nt"
        }
        
        for ext, fmt in expected_mappings.items():
            assert ext in extension_mapping
            assert extension_mapping[ext] == fmt
        
        # Check description
        assert isinstance(data["description"], str)
        assert len(data["description"]) > 0
    
    def test_supported_vocabularies(self, client):
        """Test /supported-vocabularies endpoint"""
        response = client.get("/supported-vocabularies")
        assert response.status_code == 200
        data = response.json()
        
        # Check required fields
        assert "vocabularies" in data
        assert "description" in data
        
        # Check vocabularies list
        vocabularies = data["vocabularies"]
        assert isinstance(vocabularies, list)
        assert len(vocabularies) > 0
        
        # Should include both vocabularies
        expected_vocabularies = ["skos", "ontolex"]
        for vocab in expected_vocabularies:
            assert vocab in vocabularies
        
        # Check description
        description = data["description"]
        assert isinstance(description, str)
        assert "automatically checked" in description.lower()
    
    def test_supported_vocabularies_content(self, client):
        """Test that supported vocabularies endpoint explains the automatic behavior"""
        response = client.get("/supported-vocabularies")
        assert response.status_code == 200
        data = response.json()
        
        # Should indicate that validation is automatic
        description = data["description"].lower()
        assert any(word in description for word in ["automatic", "always", "checked"])


class TestRootEndpoint:
    """Test for root endpoint"""
    
    def test_root(self, client):
        """Test / endpoint"""
        response = client.get("/")
        assert response.status_code == 200
        data = response.json()
        
        # Check structure
        assert "message" in data
        assert isinstance(data["message"], str)
        
        # Check content
        message = data["message"]
        assert "PLLOD" in message
        assert "running" in message.lower()
    
    def test_root_response_format(self, client):
        """Test that root endpoint returns proper JSON"""
        response = client.get("/")
        assert response.status_code == 200
        assert response.headers["content-type"] == "application/json"
        
        # Should be valid JSON
        data = response.json()
        assert isinstance(data, dict)