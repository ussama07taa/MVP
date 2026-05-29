<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $backupName;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->backupName = config('backup.backup.name');

        // Create an admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'tenant_id' => 1
        ]);
    }

    /** @test */
    public function it_allows_downloading_valid_backup_paths()
    {
        $filename = 'backup-2026-05-29.zip';
        $path = "{$this->backupName}/{$filename}";
        Storage::disk('local')->put($path, 'dummy content');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/backups/download?path=" . urlencode($path));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_rejects_directory_traversal_paths()
    {
        $path = "{$this->backupName}/../../secret.zip";
        
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/backups/download?path=" . urlencode($path));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Fichier introuvable ou accès refusé.']);
    }

    /** @test */
    public function it_rejects_paths_outside_backup_folder()
    {
        $path = "other-folder/backup.zip";
        Storage::disk('local')->put($path, 'dummy content');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/backups/download?path=" . urlencode($path));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_rejects_non_zip_files()
    {
        $path = "{$this->backupName}/backup.txt";
        Storage::disk('local')->put($path, 'dummy content');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/backups/download?path=" . urlencode($path));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_rejects_absolute_paths()
    {
        $path = "/etc/passwd";
        
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/backups/download?path=" . urlencode($path));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_allows_deleting_valid_backup_paths()
    {
        $filename = 'backup-to-delete.zip';
        $path = "{$this->backupName}/{$filename}";
        Storage::disk('local')->put($path, 'dummy content');

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/backups", ['path' => $path]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        Storage::disk('local')->assertMissing($path);
    }

    /** @test */
    public function it_rejects_deleting_invalid_paths()
    {
        $path = "{$this->backupName}/../../important-file.zip";
        
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/backups", ['path' => $path]);

        $response->assertStatus(404);
    }
}
