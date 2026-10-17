<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'report_cards';

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'semester_id',
        'semester',
        'fase',
        'tempat_tanggal',
        'tanggal_rapor',
        'wali_kelas_nama',
        'wali_kelas_nip',
        'kepala_sekolah_nama',
        'kepala_sekolah_nip',
        'kokurikuler_tema',
        'kokurikuler_dimensi',
        'kokurikuler_target',
        'kokurikuler_capaian',
        'kokurikuler_deskripsi',
        'sakit',
        'izin',
        'alpa',
        'catatan_wali_kelas',
        'tanggapan_orang_tua',
        'status',
        'version',
        'revised_at',
        'revised_by',
        'revision_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_rapor' => 'date',
            'sakit' => 'integer',
            'izin' => 'integer',
            'alpa' => 'integer',
            'version' => 'integer',
            'revised_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semesterRel(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ReportCardGrade::class)->orderBy('urutan', 'asc')->orderBy('id', 'asc');
    }

    public function extracurriculars(): HasMany
    {
        return $this->hasMany(ReportCardExtracurricular::class)->orderBy('urutan', 'asc')->orderBy('id', 'asc');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ReportCardRevision::class)->orderBy('version', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function revisedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }

    /**
     * Membuat snapshot revisi sebelum perubahan pada rapor final
     */
    public function createRevision(string $notes, ?int $userId = null): ReportCardRevision
    {
        $this->loadMissing(['grades', 'extracurriculars']);
        $snapshot = $this->toArray();

        $revision = ReportCardRevision::create([
            'report_card_id' => $this->id,
            'version' => $this->version ?? 1,
            'snapshot_data' => $snapshot,
            'revised_by' => $userId ?? auth()->id(),
            'revised_at' => now(),
            'revision_notes' => $notes,
        ]);

        $this->update([
            'version' => ($this->version ?? 1) + 1,
            'revised_at' => now(),
            'revised_by' => $userId ?? auth()->id(),
            'revision_notes' => $notes,
        ]);

        return $revision;
    }
}
