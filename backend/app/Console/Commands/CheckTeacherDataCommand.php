<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Staff;
use App\Models\User;
use App\Models\TeacherAssignment;

class CheckTeacherDataCommand extends Command
{
    protected $signature = 'app:check-teacher-data';
    protected $description = 'Check teachers, classes, and subjects in database';

    public function handle()
    {
        $this->info("=== CLASSES ===");
        $activeAy = \App\Models\AcademicYear::getActive();
        $this->info("Active Academic Year: " . ($activeAy ? "ID {$activeAy->id} - {$activeAy->nama_tahun}" : "NONE"));
        SchoolClass::all()->each(function ($c) {
            $wali = $c->homeroomTeacher ? $c->homeroomTeacher->nama_lengkap : 'Belum Ada';
            $this->line("ID: {$c->id} | {$c->nama} (Tingkat {$c->tingkat}) | AY_ID: {$c->academic_year_id} | Wali: {$wali} (ID: {$c->wali_kelas_id})");
        });
        $classCtrl = app(\App\Http\Controllers\Api\ClassController::class);
        $classReq = \Illuminate\Http\Request::create('/api/v1/classes', 'GET');
        $classReq->setUserResolver(fn() => User::find(2));
        $classRes = $classCtrl->index($classReq);
        $this->info("ClassController@index count: " . count($classRes->getData()->data));

        $this->info("\n=== SUBJECTS ===");
        Subject::all()->each(function ($s) {
            $this->line("ID: {$s->id} | Kode: {$s->kode} | Nama: {$s->nama} | Tematik: " . ($s->is_tematik ? 'Ya (Tema '.$s->tema_nomor.')' : 'Tidak'));
        });
        $subCtrl = app(\App\Http\Controllers\Api\SubjectController::class);
        $subReq = \Illuminate\Http\Request::create('/api/v1/subjects', 'GET', ['all' => 'true']);
        $subReq->setUserResolver(fn() => User::find(2));
        $subRes = $subCtrl->index($subReq);
        $this->info("SubjectController@index count: " . count($subRes->getData()->data));

        $this->info("\n=== TEACHERS ===");
        Teacher::all()->each(function ($t) {
            $this->line("ID: {$t->id} | Nama: {$t->nama_lengkap} | Jabatan: {$t->jabatan} | User ID: {$t->user_id}");
        });

        $this->info("\n=== STAFF ===");
        Staff::all()->each(function ($s) {
            $this->line("ID: {$s->id} | Nama: {$s->nama_lengkap} | Jabatan: {$s->jabatan} | User ID: {$s->user_id}");
        });

        $this->info("\n=== USERS ===");
        User::all()->each(function ($u) {
            $this->line("ID: {$u->id} | Name: {$u->name} | Username: {$u->username} | Role: {$u->role}");
        });

        $this->info("\n=== ASSIGNMENTS ===");
        TeacherAssignment::with(['teacher', 'subject', 'schoolClass'])->get()->each(function ($a) {
            $t = $a->teacher ? $a->teacher->nama_lengkap : '-';
            $s = $a->subject ? $a->subject->nama : '-';
            $c = $a->schoolClass ? $a->schoolClass->nama : '-';
            $this->line("Teacher: {$t} | Mapel: {$s} | Kelas: {$c}");
        });

        $this->info("\n=== TEST TEACHER CONTROLLER INDEX ===");
        $ctrl = app(\App\Http\Controllers\Api\TeacherController::class);
        $req = \Illuminate\Http\Request::create('/api/v1/teachers', 'GET');
        $user = User::find(2); // admin
        $req->setUserResolver(fn() => $user);
        $res = $ctrl->index($req);
        $this->info("TeacherController@index status: " . $res->getStatusCode());
        $data = $res->getData()->data;
        $this->info("Teachers returned count: " . count($data));
        foreach ($data as $t) {
            $this->line("- {$t->nama_lengkap} | Jabatan: {$t->jabatan} | Wali: " . (count($t->homeroom_classes) > 0 ? $t->homeroom_classes[0]->nama : 'Bukan') . " | Mapel count: " . count($t->assignments));
        }

        return 0;
    }
}
