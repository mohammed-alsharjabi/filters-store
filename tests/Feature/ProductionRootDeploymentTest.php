<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionRootDeploymentTest extends TestCase
{
    public function test_production_environment_requires_an_explicit_domain(): void
    {
        $environment = file_get_contents(base_path('.env.production.example'));

        $this->assertStringContainsString('APP_NAME="فلاتر وتحلية المياه بالرياض"'.PHP_EOL, $environment);
        $this->assertStringContainsString('APP_URL=https://example.com'.PHP_EOL, $environment);
        $this->assertStringContainsString('ASSET_URL='.PHP_EOL, $environment);
        $this->assertStringContainsString('SESSION_PATH=/'.PHP_EOL, $environment);
        $this->assertStringNotContainsString('/booking', $environment);
    }

    public function test_deployment_script_requires_the_exact_confirmed_public_root(): void
    {
        $script = file_get_contents(base_path('deploy/hostinger-release.sh'));

        $this->assertStringContainsString('EXPECTED_PUBLIC_TARGET=', $script);
        $this->assertStringContainsString('"${PUBLIC_TARGET}" != "${EXPECTED_PUBLIC_TARGET}"', $script);
        $this->assertStringNotContainsString('public_html/booking', $script);
    }
}
