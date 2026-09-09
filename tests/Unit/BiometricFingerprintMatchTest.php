<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\BiometricController;
use PHPUnit\Framework\TestCase;

class BiometricFingerprintMatchTest extends TestCase
{
    public function test_equivalent_fingerprint_template_encodings_match(): void
    {
        $controller = new BiometricController();

        $stored = 'data:application/octet-stream;base64,' . base64_encode("\x00\x01\x02\x03fingerprint-template");
        $incoming = "  " . base64_encode("\x00\x01\x02\x03fingerprint-template") . "\n\t  ";

        $this->assertTrue($controller->templatesMatch($stored, $incoming));
    }

    public function test_different_fingerprint_templates_do_not_match(): void
    {
        $controller = new BiometricController();

        $stored = base64_encode("template-one");
        $incoming = base64_encode("template-two");

        $this->assertFalse($controller->templatesMatch($stored, $incoming));
    }

    public function test_any_template_in_the_stored_template_list_matches(): void
    {
        $controller = new BiometricController();

        $stored = json_encode([
            'version' => 1,
            'templates' => [
                base64_encode('template-one'),
                base64_encode('template-two'),
            ],
        ]);

        $this->assertTrue($controller->templatesMatch($stored, base64_encode('template-two')));
    }

    public function test_legacy_binary_template_matches_base64_input(): void
    {
        $controller = new BiometricController();
        $stored = "\x00\x01\x02\x03\xFFlegacy-template";

        $this->assertTrue($controller->templatesMatch($stored, base64_encode($stored)));
        $this->assertTrue($controller->templatesMatch(base64_encode($stored), $stored));
    }
}
