<?php

namespace Tests\Unit\Services;

use App\Services\NotificationBuilder;
use App\Services\NotificationTemplate;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    /** @test */
    public function it_returns_all_templates_and_falls_back_to_info(): void
    {
        $all = NotificationTemplate::all();

        $this->assertArrayHasKey('success', $all);
        $this->assertArrayHasKey('error', $all);
        $this->assertArrayHasKey('warning', $all);
        $this->assertArrayHasKey('info', $all);
        $this->assertArrayHasKey('urgent', $all);

        $info = NotificationTemplate::get('info');
        $this->assertSame($all['info'], $info);

        $fallback = NotificationTemplate::get('non-existent');
        $this->assertSame($all['info'], $fallback);
    }

    /** @test */
    public function it_applies_template_to_builder(): void
    {
        $builder = NotificationBuilder::make();

        $builder = NotificationTemplate::apply($builder, 'success');

        $reflection = new \ReflectionClass($builder);

        $icon = $reflection->getProperty('icon');
        $icon->setAccessible(true);

        $color = $reflection->getProperty('color');
        $color->setAccessible(true);

        $priority = $reflection->getProperty('priority');
        $priority->setAccessible(true);

        $this->assertSame('check-circle', $icon->getValue($builder));
        $this->assertSame('success', $color->getValue($builder));
        $this->assertSame('normal', $priority->getValue($builder));
    }
}
