<?php

namespace Tests\Unit\Services;

use App\Events\UserAuditLogged;
use App\Models\StaffEmployee;
use App\Models\Student;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use DatabaseMigrations;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ProfileService::class);
    }

    /** @test */
    public function it_completes_profile_setup_for_student_and_creates_student_record(): void
    {
        Event::fake([UserAuditLogged::class]);

        $jurusan = \App\Models\Jurusan::create([
            'nama_jurusan' => 'Teknik',
            'deskripsi' => 'Jurusan Teknik',
        ]);
        $prodi = \App\Models\Prodi::create([
            'nama_prodi' => 'Informatika',
            'jurusan_id' => $jurusan->id,
            'jenjang' => 'S1',
            'deskripsi' => 'Prodi Informatika',
        ]);

        $user = User::factory()->create([
            'user_type' => 'mahasiswa',
            'profile_completed' => false,
            'username' => '23ABC1234',
            'phone' => null, // Ensure phone is null so service can set it
        ]);

        $data = [
            'phone' => '081234567890',
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
        ];

        $result = $this->service->completeProfileSetup($user, $data);

        $this->assertTrue($result->isProfileCompleted());
        $this->assertEquals('081234567890', $result->fresh()->phone);

        $student = Student::where('user_id', $user->id)->first();
        $this->assertNotNull($student);
        $this->assertSame($user->username, $student->nim);

        Event::assertDispatched(UserAuditLogged::class);
    }

    /** @test */
    public function it_completes_profile_setup_for_staff_and_creates_staff_record(): void
    {
        Event::fake([UserAuditLogged::class]);

        $unit = \App\Models\Unit::create([
            'nama' => 'Unit Sarpras',
            'deskripsi' => 'Unit Sarana Prasarana',
        ]);
        $position = \App\Models\Position::create([
            'nama' => 'Staff',
            'deskripsi' => 'Staff Unit',
        ]);

        $user = User::factory()->create([
            'user_type' => 'staff',
            'profile_completed' => false,
            'phone' => null, // Ensure phone is null so service can set it
        ]);

        $data = [
            'phone' => '081234567890',
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'nip' => '1234567890',
        ];

        $result = $this->service->completeProfileSetup($user, $data);

        $this->assertTrue($result->isProfileCompleted());
        $this->assertEquals('081234567890', $result->fresh()->phone);

        $staff = StaffEmployee::where('user_id', $user->id)->first();
        $this->assertNotNull($staff);
        $this->assertSame('1234567890', $staff->nip);

        Event::assertDispatched(UserAuditLogged::class);
    }

    /** @test */
    public function it_throws_when_profile_already_completed(): void
    {
        $user = User::factory()->create([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Profil sudah lengkap.');

        $this->service->completeProfileSetup($user, [
            'phone' => '081234567890',
            'jurusan_id' => 1,
            'prodi_id' => 2,
        ]);
    }

    /** @test */
    public function it_updates_profile_and_type_specific_data(): void
    {
        $jurusan = \App\Models\Jurusan::create([
            'nama_jurusan' => 'Teknik',
            'deskripsi' => 'Jurusan Teknik',
        ]);
        $prodi = \App\Models\Prodi::create([
            'nama_prodi' => 'Informatika',
            'jurusan_id' => $jurusan->id,
            'jenjang' => 'S1',
            'deskripsi' => 'Prodi Informatika',
        ]);

        $user = User::factory()->create([
            'user_type' => 'mahasiswa',
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '0800000000',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => '123',
            'angkatan' => 2024,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
            'status_mahasiswa' => 'aktif',
        ]);

        $data = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '0811111111',
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
        ];

        $updated = $this->service->updateProfile($user, $data);

        $this->assertSame('New Name', $updated->name);
        $this->assertSame('new@example.com', $updated->email);
        $this->assertSame('0811111111', $updated->phone);

        $student->refresh();
        $this->assertEquals($jurusan->id, $student->jurusan_id);
        $this->assertEquals($prodi->id, $student->prodi_id);
    }

    /** @test */
    public function it_updates_password_for_local_user_and_dispatches_audit(): void
    {
        Event::fake([UserAuditLogged::class]);

        $user = User::factory()->create([
            'password' => Hash::make('OldPassword1!'),
            'sso_id' => null,
        ]);

        $this->service->updatePassword($user, 'OldPassword1!', 'NewPassword1!');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword1!', $user->password));

        Event::assertDispatched(UserAuditLogged::class);
    }

    /** @test */
    public function it_throws_when_updating_password_for_sso_user(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword1!'),
            'sso_id' => 'sso-123',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Password akun SSO tidak dapat diubah dari aplikasi ini.');

        $this->service->updatePassword($user, 'OldPassword1!', 'NewPassword1!');
    }

    /** @test */
    public function it_throws_when_current_password_is_invalid(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword1!'),
            'sso_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password lama tidak sesuai.');

        $this->service->updatePassword($user, 'WrongPassword', 'NewPassword1!');
    }

    /** @test */
    public function it_returns_comprehensive_profile_data(): void
    {
        $jurusan = \App\Models\Jurusan::create([
            'nama_jurusan' => 'Teknik',
            'deskripsi' => 'Jurusan Teknik',
        ]);
        $prodi = \App\Models\Prodi::create([
            'nama_prodi' => 'Informatika',
            'jurusan_id' => $jurusan->id,
            'jenjang' => 'S1',
            'deskripsi' => 'Prodi Informatika',
        ]);

        $user = User::factory()->create([
            'user_type' => 'mahasiswa',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => '123',
            'angkatan' => 2024,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
            'status_mahasiswa' => 'aktif',
        ]);

        $data = $this->service->getProfileData($user);

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('is_sso_user', $data);
        $this->assertArrayHasKey('specific_data', $data);
        $this->assertArrayHasKey('has_specific_data', $data);
        $this->assertArrayHasKey('student', $data);
    }
}
