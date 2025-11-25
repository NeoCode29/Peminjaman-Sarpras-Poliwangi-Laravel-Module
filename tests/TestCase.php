<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Kelas dasar untuk seluruh pengujian aplikasi.
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
