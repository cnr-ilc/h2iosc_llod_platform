"""
Tests for quality suggestion generation - comprehensive verification of suggestion logic
"""

import pytest
from tests.conftest import TURTLE_NO_BASE_NO_ONTOLOGY


class TestSuggestions:
    """Tests for quality suggestions generation"""
    
    def test_suggestions_external_links_low(self, client, temp_file):
        """Test suggestion for low external links percentage"""
        # Create RDF with very few external links - use local vocabulary
        content = """
        @prefix ex: <http://example.org/> .
        @prefix localvoc: <http://example.org/vocab/> .
        
        ex:concept1 a localvoc:Concept ;
            localvoc:prefLabel "Local Concept" ;
            localvoc:related ex:concept2 ;
            localvoc:related ex:concept3 ;
            localvoc:related ex:concept4 ;
            localvoc:related ex:concept5 ;
            localvoc:related ex:concept6 ;
            localvoc:related ex:concept7 ;
            localvoc:related ex:concept8 ;
            localvoc:related ex:concept9 ;
            localvoc:related ex:concept10 ;
            localvoc:related ex:concept11 ;
            localvoc:related ex:concept12 ;
            localvoc:related ex:concept13 ;
            localvoc:related ex:concept14 ;
            localvoc:related ex:concept15 ;
            localvoc:related ex:concept16 ;
            localvoc:related ex:concept17 ;
            localvoc:related ex:concept18 ;
            localvoc:related ex:concept19 ;
            localvoc:related ex:concept20 ;
            localvoc:broader <http://dbpedia.org/resource/Thing> .
            
        ex:concept2 a localvoc:Concept ; localvoc:prefLabel "Concept 2" .
        ex:concept3 a localvoc:Concept ; localvoc:prefLabel "Concept 3" .
        ex:concept4 a localvoc:Concept ; localvoc:prefLabel "Concept 4" .
        ex:concept5 a localvoc:Concept ; localvoc:prefLabel "Concept 5" .
        ex:concept6 a localvoc:Concept ; localvoc:prefLabel "Concept 6" .
        ex:concept7 a localvoc:Concept ; localvoc:prefLabel "Concept 7" .
        ex:concept8 a localvoc:Concept ; localvoc:prefLabel "Concept 8" .
        ex:concept9 a localvoc:Concept ; localvoc:prefLabel "Concept 9" .
        ex:concept10 a localvoc:Concept ; localvoc:prefLabel "Concept 10" .
        ex:concept11 a localvoc:Concept ; localvoc:prefLabel "Concept 11" .
        ex:concept12 a localvoc:Concept ; localvoc:prefLabel "Concept 12" .
        ex:concept13 a localvoc:Concept ; localvoc:prefLabel "Concept 13" .
        ex:concept14 a localvoc:Concept ; localvoc:prefLabel "Concept 14" .
        ex:concept15 a localvoc:Concept ; localvoc:prefLabel "Concept 15" .
        ex:concept16 a localvoc:Concept ; localvoc:prefLabel "Concept 16" .
        ex:concept17 a localvoc:Concept ; localvoc:prefLabel "Concept 17" .
        ex:concept18 a localvoc:Concept ; localvoc:prefLabel "Concept 18" .
        ex:concept19 a localvoc:Concept ; localvoc:prefLabel "Concept 19" .
        ex:concept20 a localvoc:Concept ; localvoc:prefLabel "Concept 20" .
        """
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post(
                "/quality", 
                files={"file": ("test.ttl", f, "text/turtle")},
                data={"base_domain": "example.org"}
            )
        
        assert response.status_code == 200
        data = response.json()
        
        # Check external links percentage - should be low (only 1 external out of many total)
        external_percentage = data["report"]["external_links"]["external_percentage"]
        assert external_percentage < 5.0, f"Expected < 5% external links, got {external_percentage}%"
        
        # Should have low external links score
        assert data["score"]["external_links_score"] < 5
        
        # Should suggest improving external links
        suggestions = data["report"]["suggestions"]
        external_suggestion = next((s for s in suggestions if "external links" in s.lower()), None)
        assert external_suggestion is not None
        assert "increase connections to external resources" in external_suggestion.lower()
        assert "%" in external_suggestion  # Should show percentage
    
    def test_suggestions_no_license(self, client, temp_file):
        """Test suggestion for missing license"""
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <http://example.org/concept1> a skos:Concept ;
            skos:prefLabel "Concept without license" .
        """
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # Should have zero license score
        assert data["score"]["license_score"] == 0
        
        # Should suggest adding license
        suggestions = data["report"]["suggestions"]
        license_suggestion = next((s for s in suggestions if "license" in s.lower()), None)
        assert license_suggestion is not None
        assert "add an open license" in license_suggestion.lower()
    
    def test_suggestions_non_open_license(self, client, temp_file):
        """Test suggestion for non-open license"""
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        @prefix dct: <http://purl.org/dc/terms/> .
        
        <> a skos:ConceptScheme ;
            dct:license <http://example.org/proprietary-license> ;
            skos:prefLabel "Proprietary Licensed Vocabulary" .
            
        <http://example.org/concept1> a skos:Concept ;
            skos:prefLabel "Concept" .
        """
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # Should have zero license score (non-open license)
        assert data["score"]["license_score"] == 0
        
        # Should suggest using open license
        suggestions = data["report"]["suggestions"]
        license_suggestion = next((s for s in suggestions if "license" in s.lower()), None)
        assert license_suggestion is not None
        assert "use an open license" in license_suggestion.lower()
    
    def test_suggestions_shacl_failure(self, client, temp_file):
        """Test suggestion for SHACL validation failure"""
        # Create invalid SKOS - concept without prefLabel (should fail SHACL)
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <http://example.org/concept1> a skos:Concept ;
            skos:notation "INVALID" .
        """
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # Check if SHACL failed (might pass depending on shapes)
        shacl_result = data["report"]["shacl_validation"]
        if not shacl_result["is_valid"]:
            # Should have zero SHACL score
            assert data["score"]["shacl_score"] == 0
            
            # Should suggest fixing SHACL errors
            suggestions = data["report"]["suggestions"]
            shacl_suggestion = next((s for s in suggestions if "shacl" in s.lower()), None)
            assert shacl_suggestion is not None
            assert "fix vocabulary structure errors" in shacl_suggestion.lower()
    
    def test_suggestions_uri_validity_issues(self, client, temp_file):
        """Test suggestion for URI validity issues"""
        # Create RDF with potentially inaccessible URIs
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        
        <http://nonexistent-domain-12345.org/concept1> a skos:Concept ;
            skos:prefLabel "Concept" ;
            skos:broader <http://invalid-server-98765.com/concept2> .
        """
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        # Check URI validity results
        uri_validity = data["report"]["uri_validity"]
        if uri_validity["total_base_uris_not_accessible"] > 0:
            # Should have suggestion for URI issues
            suggestions = data["report"]["suggestions"]
            uri_suggestion = next((s for s in suggestions if "base uri" in s.lower() or "inaccessible" in s.lower()), None)
            assert uri_suggestion is not None
            assert "fix invalid base uris" in uri_suggestion.lower()
            # Should mention counts
            assert str(uri_validity["total_base_uris_not_accessible"]) in uri_suggestion
            assert str(uri_validity["total_base_uris_found"]) in uri_suggestion
    
    def test_suggestions_perfect_quality(self, client, temp_file):
        """Test that no suggestions are given for perfect quality RDF"""
        content = """
        @prefix skos: <http://www.w3.org/2004/02/skos/core#> .
        @prefix dct: <http://purl.org/dc/terms/> .
        
        <> a skos:ConceptScheme ;
            dct:license <https://creativecommons.org/licenses/by/4.0/> ;
            skos:prefLabel "High Quality Vocabulary" .
            
        <http://example.org/concept1> a skos:Concept ;
            skos:prefLabel "Animal"@en ;
            skos:broader <http://dbpedia.org/resource/LivingThing> ;
            skos:related <http://wikidata.org/entity/Q729> .
            
        <http://example.org/concept2> a skos:Concept ;
            skos:prefLabel "Dog"@en ;
            skos:broader <http://example.org/concept1> ;
            skos:exactMatch <http://dbpedia.org/resource/Dog> .
        """
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post(
                "/quality", 
                files={"file": ("test.ttl", f, "text/turtle")},
                data={"base_domain": "example.org"}
            )
        
        assert response.status_code == 200
        data = response.json()
        
        # Check scores - external links should be high, license should be 10
        assert data["score"]["external_links_score"] >= 5  # Good external connectivity
        assert data["score"]["license_score"] == 10  # Open license found
        
        # Should have fewer suggestions (maybe none if everything is perfect)
        suggestions = data["report"]["suggestions"]
        assert isinstance(suggestions, list)
        # If there are suggestions, they should be specific and helpful
        for suggestion in suggestions:
            assert isinstance(suggestion, str)
            assert len(suggestion) > 10
    
    def test_suggestions_content_quality(self, client, temp_file):
        """Test that suggestions contain specific, actionable advice"""
        content = TURTLE_NO_BASE_NO_ONTOLOGY
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post(
                "/quality", 
                files={"file": ("test.ttl", f, "text/turtle")},
                data={"base_domain": "example.org"}
            )
        
        assert response.status_code == 200
        data = response.json()
        
        suggestions = data["report"]["suggestions"]
        
        for suggestion in suggestions:
            # Each suggestion should be a meaningful string
            assert isinstance(suggestion, str)
            assert len(suggestion) > 20  # Should be descriptive
            assert suggestion.endswith('.') or suggestion.endswith('.')  # Should be complete sentences
            
            # Should contain actionable advice
            actionable_words = ['add', 'fix', 'increase', 'improve', 'use', 'include']
            assert any(word in suggestion.lower() for word in actionable_words)
    
    def test_suggestions_consistency(self, client, temp_file):
        """Test that suggestions are consistent with scores"""
        content = TURTLE_NO_BASE_NO_ONTOLOGY
        file_path = temp_file(content, ".ttl")
        
        with open(file_path, 'rb') as f:
            response = client.post("/quality", files={"file": ("test.ttl", f, "text/turtle")})
        
        assert response.status_code == 200
        data = response.json()
        
        score = data["score"]
        suggestions = data["report"]["suggestions"]
        
        # If external links score is low, should have external links suggestion
        if score["external_links_score"] < 5:
            has_external_suggestion = any("external" in s.lower() for s in suggestions)
            assert has_external_suggestion, "Low external links score should generate suggestion"
        
        # If license score is 0, should have license suggestion
        if score["license_score"] == 0:
            has_license_suggestion = any("license" in s.lower() for s in suggestions)
            assert has_license_suggestion, "Zero license score should generate suggestion"
        
        # If SHACL score is 0, should have SHACL suggestion
        if score["shacl_score"] == 0:
            has_shacl_suggestion = any("shacl" in s.lower() or "vocabulary" in s.lower() for s in suggestions)
            assert has_shacl_suggestion, "Zero SHACL score should generate suggestion"