<?php

namespace App\Enums;

enum RdfFormat: string
{
    case TURTLE = 'turtle';
    case XML = 'xml';
    case NT = 'nt';
    case N3 = 'n3';
    case JSON_LD = 'json-ld';
    case NQUADS = 'nquads';
    case TRIG = 'trig';

    /**
     * Get RDF format based on file extension
     */
    public static function fromExtension(string $filename): self
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match($extension) {
            'ttl' => self::TURTLE,
            'rdf', 'owl' => self::XML,
            'nt' => self::NT,
            'n3' => self::N3,
            'jsonld', 'json' => self::JSON_LD,
            'nq' => self::NQUADS,
            'trig' => self::TRIG,
            default => self::XML, // Default to xml if extension not found
        };
    }

    /**
     * Get RDF format based on MIME type
     */
    public static function toValidationAPIFormat(string $mimeType): self
    {
        return match(strtolower($mimeType)) {
            'text/turtle' => self::TURTLE,
            'application/rdf+xml', 'text/xml', 'application/xml' => self::XML,
            'application/n-triples', 'text/plain' => self::NT,
            'text/n3' => self::N3,
            'application/ld+json' => self::JSON_LD,
            'application/n-quads' => self::NQUADS,
            'application/trig' => self::TRIG,
            default => self::XML, // Default to xml if mime type not recognized
        };
    }

    /**
     * Get Semantic Turkey format name from database MIME type
     */
    public static function toSTFormatName(string $mimeType): string
    {
        return match(strtolower($mimeType)) {
            'text/turtle' => 'Turtle',
            'application/rdf+xml', 'text/xml', 'application/xml' => 'RDF/XML',
            'application/n-triples', 'text/plain' => 'N-Triples',
            'text/n3' => 'N3',
            'application/ld+json' => 'JSON-LD',
            'application/n-quads' => 'N-Quads',
            'application/trig' => 'TriG',
            default => 'RDF/XML', // Default to RDF/XML if mime type not recognized
        };
    }

    /**
     * Get all supported extensions
     */
    public static function getSupportedExtensions(): array
    {
        return ['ttl', 'rdf', 'owl', 'nt', 'n3', 'jsonld', 'json', 'nq', 'trig'];
    }
}