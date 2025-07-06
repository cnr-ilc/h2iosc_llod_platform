"""
Tests for the /quality endpoint - Comprehensive RDF quality analysis
"""

import pytest
from tests.conftest import (
    TURTLE_WITH_BASE, TURTLE_NO_BASE_NO_ONTOLOGY, 
    TURTLE_WITH_LICENSE, INVALID_RDF
)


class TestQualityEndpoint:
    """Tests for /quality endpoint"""
    
    def test_quality_with_base_domain(self, client, temp_file):
        """Test quality analysis with base_domain provided"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post(
                "/quality", 
                files={"file": ("test.ttl", f, "text/turtle")},
                data={"base_domain": "example.org"}
            )
        
        assert response.status_code == 200
        data = response.json()
        
        # Check structure
        assert "score" in data
        assert "report" in data
        
        # Check scores
        score = data["score"]
        required_score_fields = ["total_score", "external_links_score", "uri_validity_score", "license_score", "shacl_score"]
        for field in required_score_fields:
            assert field in score
            assert isinstance(score[field], int)
            assert 0 <= score[field] <= 10
        
        # Check report
        report = data["report"]
        required_report_fields = ["external_links", "uri_validity", "license_info", "shacl_validation", "suggestions"]
        for field in required_report_fields:
            assert field in report
        
        # External links should show some percentage < 100 (since base_domain provided)
        assert report["external_links"]["external_percentage"] < 100
    
    def test_quality_without_base_domain(self, client, temp_file):
        """Test quality analysis without base_domain (should give 100% external)"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # External links should be 100% when no base_domain provided
        assert data["report"]["external_links"]["external_percentage"] == 100.0
        assert "No base domain provided" in data["report"]["external_links"]["note"]
        
        # Should get max external links score (10)
        assert data["score"]["external_links_score"] == 10
    
    def test_quality_shacl_validation_always_present(self, client, temp_file):
        """Test that SHACL validation is always performed"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # SHACL validation should always be present
        assert "shacl_validation" in data["report"]
        assert "shacl_score" in data["score"]
        assert data["score"]["shacl_score"] is not None
        assert isinstance(data["score"]["shacl_score"], int)
        
        # Should have individual_results for each vocabulary
        shacl_result = data["report"]["shacl_validation"]
        assert "individual_results" in shacl_result
        assert len(shacl_result["individual_results"]) >= 1  # At least one vocabulary tested
        
        # Check individual result structure
        for result in shacl_result["individual_results"]:
            assert "vocabulary" in result
            assert "is_valid" in result
            assert "errors" in result
            assert isinstance(result["is_valid"], bool)
            assert isinstance(result["errors"], list)
    
    def test_quality_license_detection(self, client, temp_file):
        """Test license detection in quality analysis"""
        file_path = temp_file(TURTLE_WITH_LICENSE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # Should detect the Creative Commons license
        license_info = data["report"]["license_info"]
        assert "licenses_found" in license_info
        assert "is_open_license" in license_info
        assert license_info["is_open_license"] is True
        assert len(license_info["licenses_found"]) > 0
        
        # Should get max license score
        assert data["score"]["license_score"] == 10
    
    def test_quality_score_calculation(self, client, temp_file):
        """Test that quality score calculation follows the expected weights"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        score = data["score"]
        
        # Verify score calculation with weights: 25% external + 25% URI + 30% license + 20% SHACL
        expected_total = (
            score["external_links_score"] * 0.25 +
            score["uri_validity_score"] * 0.25 +
            score["license_score"] * 0.30 +
            score["shacl_score"] * 0.20
        )
        
        # Allow for rounding differences
        assert abs(score["total_score"] - round(expected_total)) <= 1
    
    def test_quality_suggestions_generation(self, client, temp_file):
        """Test that quality suggestions are generated appropriately"""
        file_path = temp_file(TURTLE_NO_BASE_NO_ONTOLOGY, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post(
                "/quality", 
                files={"file": ("test.ttl", f, "text/turtle")},
                data={"base_domain": "example.org"}
            )
        
        assert response.status_code == 200
        data = response.json()
        
        suggestions = data["report"]["suggestions"]
        assert isinstance(suggestions, list)
        
        # Should have suggestions for low scores (no license in this test data)
        assert len(suggestions) > 0
        
        # Check that suggestions are meaningful strings
        for suggestion in suggestions:
            assert isinstance(suggestion, str)
            assert len(suggestion) > 10  # Should be descriptive
    
    def test_quality_invalid_rdf(self, client, temp_file):
        """Test quality endpoint with invalid RDF"""
        file_path = temp_file(INVALID_RDF, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 400
        data = response.json()
        assert "error" in data
        assert "not valid" in data["error"]
    
    def test_quality_uri_validity_check(self, client, temp_file):
        """Test URI validity checking in quality analysis"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        uri_validity = data["report"]["uri_validity"]
        required_fields = ["total_base_uris_found", "total_base_uris_accessible", "total_base_uris_not_accessible", "not_accessible_uris"]
        
        for field in required_fields:
            assert field in uri_validity
        
        # Check types and logical consistency
        assert isinstance(uri_validity["total_base_uris_found"], int)
        assert isinstance(uri_validity["total_base_uris_accessible"], int)
        assert isinstance(uri_validity["total_base_uris_not_accessible"], int)
        assert isinstance(uri_validity["not_accessible_uris"], list)
        
        # Logical consistency
        total = uri_validity["total_base_uris_found"]
        accessible = uri_validity["total_base_uris_accessible"]
        not_accessible = uri_validity["total_base_uris_not_accessible"]
        
        assert total == accessible + not_accessible
    
    def test_quality_response_structure(self, client, temp_file):
        """Test that quality response has correct structure"""
        file_path = temp_file(TURTLE_WITH_BASE, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # Top-level structure
        assert "score" in data
        assert "report" in data
        
        # Score structure
        score = data["score"]
        score_fields = ["total_score", "external_links_score", "uri_validity_score", "license_score", "shacl_score"]
        for field in score_fields:
            assert field in score
            assert isinstance(score[field], int)
            assert 0 <= score[field] <= 10
        
        # Report structure
        report = data["report"]
        report_fields = ["external_links", "uri_validity", "license_info", "shacl_validation", "suggestions"]
        for field in report_fields:
            assert field in report
        
        assert isinstance(report["suggestions"], list)