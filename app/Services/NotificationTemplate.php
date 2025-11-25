<?php

namespace App\Services;

class NotificationTemplate
{
    /**
     * Get all available templates
     */
    public static function all(): array
    {
        return [
            'success' => [
                'icon' => 'check-circle',
                'color' => 'success',
                'priority' => 'normal',
            ],
            'error' => [
                'icon' => 'x-circle',
                'color' => 'danger',
                'priority' => 'high',
            ],
            'warning' => [
                'icon' => 'exclamation-triangle',
                'color' => 'warning',
                'priority' => 'normal',
            ],
            'info' => [
                'icon' => 'information-circle',
                'color' => 'info',
                'priority' => 'normal',
            ],
            'urgent' => [
                'icon' => 'exclamation-circle',
                'color' => 'danger',
                'priority' => 'urgent',
            ],
        ];
    }

    /**
     * Get template configuration
     */
    public static function get(string $template): array
    {
        return self::all()[$template] ?? self::all()['info'];
    }

    /**
     * Apply template to builder
     */
    public static function apply(NotificationBuilder $builder, string $template): NotificationBuilder
    {
        $config = self::get($template);

        return $builder
            ->icon($config['icon'])
            ->color($config['color'])
            ->priority($config['priority']);
    }
}
