#!/usr/bin/env python3
"""
Test runner for the RDF Validation API

Usage:
    uv run run_tests.py              # Run all tests
    uv run run_tests.py health       # Run only health endpoint tests
    uv run run_tests.py quality      # Run only quality endpoint tests
    uv run run_tests.py base         # Run only base URI extraction tests
    uv run run_tests.py support      # Run only support endpoint tests
    uv run run_tests.py suggestions  # Run only suggestions tests
    uv run run_tests.py -v           # Verbose output
    uv run run_tests.py --coverage   # Run with coverage report
"""

import sys
import subprocess
import os

def run_tests(test_filter=None, verbose=False, coverage=False):
    """Run pytest with optional filtering"""
    # sys.executable: funziona sia con "uv run" sia dentro il virtualenv del container
    cmd = [sys.executable, "-m", "pytest", "tests/"]
    
    if coverage:
        cmd.extend(["--cov=src", "--cov-report=html", "--cov-report=term"])
    
    if verbose or "-v" in sys.argv:
        cmd.append("-v")
    
    if test_filter:
        if test_filter == "health":
            cmd.append("tests/test_health_endpoint.py")
        elif test_filter == "quality":
            cmd.append("tests/test_quality_endpoint.py")
        elif test_filter == "base":
            cmd.append("tests/test_base_uri_extraction.py")
        elif test_filter == "support":
            cmd.append("tests/test_support_endpoints.py")
        elif test_filter == "suggestions":
            cmd.append("tests/test_suggestions.py")
    
    # Add colored output
    cmd.extend(["--color=yes"])
    
    print(f"Running: {' '.join(cmd)}")
    print("-" * 50)
    
    try:
        result = subprocess.run(cmd, check=True)
        print("\n✅ All tests passed!")
        
        if coverage:
            print("📊 Coverage report generated in htmlcov/index.html")
        
        return True
    except subprocess.CalledProcessError as e:
        print(f"\n❌ Tests failed with exit code {e.returncode}")
        return False
    except FileNotFoundError:
        print("❌ pytest not found. Install with: pip install pytest httpx pytest-cov")
        return False

def show_help():
    """Show usage help"""
    print(__doc__)

if __name__ == "__main__":
    test_filter = None
    coverage = "--coverage" in sys.argv
    
    if "--help" in sys.argv or "-h" in sys.argv:
        show_help()
        sys.exit(0)
    
    if len(sys.argv) > 1:
        for arg in sys.argv[1:]:
            if arg not in ["-v", "--verbose", "--coverage"]:
                test_filter = arg
                break
    
    success = run_tests(test_filter, coverage=coverage)
    sys.exit(0 if success else 1)