<?php

use App\Models\AcademicYear;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AssetMaintenance;
use App\Models\FeeType;
use App\Models\FinanceTransaction;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\FeeTypeSeeder;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SchoolProfileSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);
    $this->seed(SchoolProfileSeeder::class);
    $this->seed(SettingSeeder::class);
    $this->seed(FeeTypeSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $roleWali = Role::where('nama', Role::WALI)->first();

    // Admin Tata Usaha / Keuangan
    $this->admin = User::create([
        'nama' => 'Bendahara Sekolah',
        'username' => 'bendahara',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);

    // Wali 1
    $this->userWali1 = User::create([
        'nama' => 'Wali Siswa 1',
        'username' => 'wali_keu1',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $this->guardian1 = Guardian::create([
        'user_id' => $this->userWali1->id,
        'nik' => '3302011005850011',
        'nama_lengkap' => 'Wali Siswa 1',
        'hubungan' => 'ayah',
        'jenis_kelamin' => 'L',
        'alamat_jalan' => 'Jl. Pahlawan No. 1',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567091',
        'status' => 'aktif',
    ]);

    // Wali 2
    $this->userWali2 = User::create([
        'nama' => 'Wali Siswa 2',
        'username' => 'wali_keu2',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $this->guardian2 = Guardian::create([
        'user_id' => $this->userWali2->id,
        'nik' => '3302011005850022',
        'nama_lengkap' => 'Wali Siswa 2',
        'hubungan' => 'ibu',
        'jenis_kelamin' => 'P',
        'alamat_jalan' => 'Jl. Pahlawan No. 2',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567092',
        'status' => 'aktif',
    ]);

    $this->year = AcademicYear::create([
        'nama' => '2026/2027',
        'semester' => 'ganjil',
        'tgl_mulai' => '2026-07-15',
        'tgl_selesai' => '2026-12-20',
        'is_aktif' => true,
    ]);

    $this->room = Room::create(['kode' => 'R301', 'nama' => 'Ruang 3A', 'kapasitas' => 30, 'jenis' => 'kelas']);
    $this->class = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '3A',
        'tingkat' => 3,
        'nama' => '3A Ali bin Abi Thalib',
        'room_id' => $this->room->id,
        'kapasitas' => 28,
        'status' => 'aktif',
    ]);

    // Siswa 1 (Wali 1)
    $this->student1 = Student::create([
        'nis' => '26031',
        'nisn' => '0012345631',
        'nik' => '3302012001150031',
        'nama_lengkap' => 'Faris Al-Fatih',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2017-03-10',
        'agama' => Religion::first()->id,
        'alamat_jalan' => 'Jl. Pahlawan No. 1',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2024-07-15',
        'tahun_masuk' => 2024,
        'class_id' => $this->class->id,
        'status' => 'aktif',
    ]);
    $this->student1->guardians()->attach($this->guardian1->id, [
        'hubungan' => 'ayah',
        'is_kontak_utama' => true,
        'boleh_lihat_keuangan' => true,
        'boleh_jemput' => true,
    ]);

    // Siswa 2 (Wali 2)
    $this->student2 = Student::create([
        'nis' => '26032',
        'nisn' => '0012345632',
        'nik' => '3302012001150032',
        'nama_lengkap' => 'Zahra Humaira',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2017-06-25',
        'agama' => Religion::first()->id,
        'alamat_jalan' => 'Jl. Pahlawan No. 2',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2024-07-15',
        'tahun_masuk' => 2024,
        'class_id' => $this->class->id,
        'status' => 'aktif',
    ]);
    $this->student2->guardians()->attach($this->guardian2->id, [
        'hubungan' => 'ibu',
        'is_kontak_utama' => true,
        'boleh_lihat_keuangan' => true,
        'boleh_jemput' => true,
    ]);
});

test('admin can manage fee types and generate batch invoices for a class', function () {
    Sanctum::actingAs($this->admin);

    $spp = FeeType::where('kode', 'SPP')->first();

    // 1. Generate batch SPP for class 3A
    $batchRes = $this->postJson('/api/v1/invoices/generate-batch', [
        'fee_type_id' => $spp->id,
        'class_id' => $this->class->id,
        'periode' => '2026-08',
        'jatuh_tempo' => '2026-08-10',
    ]);

    $batchRes->assertCreated();
    expect($batchRes->json('data.created'))->toBe(2);
    expect($batchRes->json('data.skipped'))->toBe(0);

    // 2. Duplicate generation skips existing
    $batchRes2 = $this->postJson('/api/v1/invoices/generate-batch', [
        'fee_type_id' => $spp->id,
        'class_id' => $this->class->id,
        'periode' => '2026-08',
        'jatuh_tempo' => '2026-08-10',
    ]);

    $batchRes2->assertCreated();
    expect($batchRes2->json('data.created'))->toBe(0);
    expect($batchRes2->json('data.skipped'))->toBe(2);

    // Verify invoices in DB
    $inv1 = Invoice::where('student_id', $this->student1->id)->where('periode', '2026-08')->first();
    expect($inv1)->not->toBeNull();
    expect($inv1->status)->toBe('belum_bayar');
    expect($inv1->jumlah)->toBe(350000.0);
    expect($inv1->sisa)->toBe(350000.0);
});

test('admin can record partial and full payments, generate receipt, and reject overpayment', function () {
    Sanctum::actingAs($this->admin);

    $spp = FeeType::where('kode', 'SPP')->first();

    $invoice = Invoice::create([
        'no_tagihan' => Invoice::generateNumber(),
        'student_id' => $this->student1->id,
        'fee_type_id' => $spp->id,
        'academic_year_id' => $this->year->id,
        'periode' => '2026-08',
        'jumlah' => 350000,
        'jumlah_dibayar' => 0,
        'sisa' => 350000,
        'status' => 'belum_bayar',
        'jatuh_tempo' => '2026-08-10',
        'created_by' => $this->admin->id,
    ]);

    // 1. Overpayment rejected
    $overRes = $this->postJson('/api/v1/payments', [
        'invoice_id' => $invoice->id,
        'jumlah' => 400000,
        'metode' => 'tunai',
    ]);
    $overRes->assertStatus(422);
    expect($overRes->json('message'))->toContain('melebihi sisa tagihan');

    // 2. Partial payment (Rp 200.000)
    $partialRes = $this->postJson('/api/v1/payments', [
        'invoice_id' => $invoice->id,
        'jumlah' => 200000,
        'metode' => 'transfer',
        'tanggal' => now()->toDateString(),
        'bukti' => UploadedFile::fake()->create('struk.jpg', 200, 'image/jpeg'),
    ]);

    $partialRes->assertCreated();
    $payment1Id = $partialRes->json('data.id');

    $invoice->refresh();
    expect($invoice->jumlah_dibayar)->toBe(200000.0);
    expect($invoice->sisa)->toBe(150000.0);
    expect($invoice->status)->toBe('sebagian');

    // Verify cash-in transaction created
    $trans = FinanceTransaction::where('payment_id', $payment1Id)->first();
    expect($trans)->not->toBeNull();
    expect($trans->tipe)->toBe('masuk');
    expect($trans->jumlah)->toBe(200000.0);

    // 3. Settle remaining balance (Rp 150.000)
    $fullRes = $this->postJson('/api/v1/payments', [
        'invoice_id' => $invoice->id,
        'jumlah' => 150000,
        'metode' => 'tunai',
        'tanggal' => now()->toDateString(),
    ]);

    $fullRes->assertCreated();
    $payment2Id = $fullRes->json('data.id');

    $invoice->refresh();
    expect($invoice->jumlah_dibayar)->toBe(350000.0);
    expect($invoice->sisa)->toBe(0.0);
    expect($invoice->status)->toBe('lunas');

    // 4. Receipt data check
    $receiptRes = $this->getJson("/api/v1/payments/{$payment2Id}/receipt");
    $receiptRes->assertOk();
    expect($receiptRes->json('data.student.nama'))->toBe('Faris Al-Fatih');
    expect($receiptRes->json('data.terbilang'))->toContain('Seratus Lima Puluh Ribu');
});

test('admin can cancel payment with reversing journal and balance is restored', function () {
    Sanctum::actingAs($this->admin);

    $spp = FeeType::where('kode', 'SPP')->first();

    $invoice = Invoice::create([
        'no_tagihan' => Invoice::generateNumber(),
        'student_id' => $this->student1->id,
        'fee_type_id' => $spp->id,
        'academic_year_id' => $this->year->id,
        'periode' => '2026-08',
        'jumlah' => 350000,
        'jumlah_dibayar' => 0,
        'sisa' => 350000,
        'status' => 'belum_bayar',
        'jatuh_tempo' => '2026-08-10',
        'created_by' => $this->admin->id,
    ]);

    // Record payment of Rp 350.000
    $payRes = $this->postJson('/api/v1/payments', [
        'invoice_id' => $invoice->id,
        'jumlah' => 350000,
        'metode' => 'tunai',
    ]);
    $payRes->assertCreated();
    $paymentId = $payRes->json('data.id');

    expect($invoice->fresh()->status)->toBe('lunas');

    // FR-KEU-08: Cancel payment with reversing entry
    $cancelRes = $this->postJson("/api/v1/payments/{$paymentId}/cancel", [
        'alasan_batal' => 'Salah input siswa, uang tunai dikembalikan kepada wali murid.',
    ]);

    $cancelRes->assertOk();

    // Check payment status
    $payment = Payment::find($paymentId);
    expect($payment->is_batal)->toBeTrue();
    expect($payment->alasan_batal)->toContain('Salah input siswa');

    // Check reversing transaction created
    $reversal = FinanceTransaction::where('is_pembalik', true)->first();
    expect($reversal)->not->toBeNull();
    expect($reversal->tipe)->toBe('keluar');
    expect($reversal->jumlah)->toBe(350000.0);

    // Invoice status restored
    $invoice->refresh();
    expect($invoice->jumlah_dibayar)->toBe(0.0);
    expect($invoice->sisa)->toBe(350000.0);
    expect($invoice->status)->toBe('belum_bayar');
});

test('admin can record operational cash transactions and generate financial reports', function () {
    Sanctum::actingAs($this->admin);

    $today = now()->toDateString();

    // Inflow
    $this->postJson('/api/v1/finance-transactions', [
        'tipe' => 'masuk',
        'kategori' => 'dana_bos',
        'jumlah' => 15000000,
        'tanggal' => $today,
        'keterangan' => 'Pencairan Dana BOS Tahap 1',
    ])->assertCreated();

    // Outflow
    $this->postJson('/api/v1/finance-transactions', [
        'tipe' => 'keluar',
        'kategori' => 'atk',
        'jumlah' => 1250000,
        'tanggal' => $today,
        'keterangan' => 'Pembelian kertas HVS dan spidol whiteboard',
    ])->assertCreated();

    // Financial report
    $reportRes = $this->getJson("/api/v1/reports/finance?start_date={$today}&end_date={$today}");
    $reportRes->assertOk();
    expect($reportRes->json('data.ringkasan.total_pemasukan'))->toEqual(15000000);
    expect($reportRes->json('data.ringkasan.total_pengeluaran'))->toEqual(1250000);
    expect($reportRes->json('data.ringkasan.surplus_defisit'))->toEqual(13750000);
    expect($reportRes->json('data.breakdown_pengeluaran.atk'))->toEqual(1250000);
});

test('admin can manage assets, record maintenance, and track equipment loan', function () {
    Sanctum::actingAs($this->admin);

    // 1. Create asset
    $assetRes = $this->postJson('/api/v1/assets', [
        'kode' => 'AST-PROJ-01',
        'nama' => 'Proyektor Epson EB-X500',
        'kategori' => 'elektronik',
        'lokasi' => 'Laboratorium Komputer',
        'room_id' => $this->room->id,
        'tahun_perolehan' => 2025,
        'nilai' => 6500000,
        'kondisi' => 'baik',
        'jumlah' => 1,
        'satuan' => 'unit',
    ]);

    $assetRes->assertCreated();
    $assetId = $assetRes->json('data.id');

    // 2. Record maintenance (lamp replacement)
    $maintRes = $this->postJson("/api/v1/assets/{$assetId}/maintenance", [
        'tanggal' => now()->toDateString(),
        'deskripsi' => 'Penggantian lampu proyektor dan pembersihan filter debu',
        'biaya' => 850000,
        'pelaksana' => 'CV Mitra Komputer',
        'kondisi_sesudah' => 'baik',
    ]);

    $maintRes->assertCreated();
    expect($maintRes->json('data.biaya'))->toEqual(850000);

    // 3. Borrow asset
    $loanRes = $this->postJson("/api/v1/assets/{$assetId}/borrow", [
        'peminjam_nama' => 'Ustadz Zaid',
        'tgl_rencana_kembali' => now()->addDays(2)->toDateString(),
        'keperluan' => 'Presentasi materi KBM di kelas 3A',
    ]);

    $loanRes->assertCreated();
    $loanId = $loanRes->json('data.id');
    expect($loanRes->json('data.status'))->toBe('dipinjam');

    // 4. Return loan
    $returnRes = $this->postJson("/api/v1/assets/loans/{$loanId}/return", [
        'kondisi_sesudah' => 'baik',
        'catatan' => 'Dikembalikan tepat waktu dalam kondisi baik dan lengkap kabelnya.',
    ]);

    $returnRes->assertOk();
    expect($returnRes->json('data.status'))->toBe('dikembalikan');

    // 5. Inventory list and summary
    $indexRes = $this->getJson('/api/v1/assets');
    $indexRes->assertOk();
    expect($indexRes->json('summary.total_aset'))->toBe(1);
    expect($indexRes->json('summary.total_nilai'))->toEqual(6500000);
    expect($indexRes->json('summary.kondisi.baik'))->toBe(1);
});

test('guardian can view their child invoices and payment receipts only', function () {
    $spp = FeeType::where('kode', 'SPP')->first();

    // Invoice for Student 1 (Wali 1)
    $inv1 = Invoice::create([
        'no_tagihan' => 'INV/TEST/001',
        'student_id' => $this->student1->id,
        'fee_type_id' => $spp->id,
        'academic_year_id' => $this->year->id,
        'periode' => '2026-08',
        'jumlah' => 350000,
        'jumlah_dibayar' => 350000,
        'sisa' => 0,
        'status' => 'lunas',
        'jatuh_tempo' => '2026-08-10',
        'created_by' => $this->admin->id,
    ]);

    Payment::create([
        'no_kuitansi' => 'KWI/TEST/001',
        'invoice_id' => $inv1->id,
        'tanggal' => now()->toDateString(),
        'jumlah' => 350000,
        'metode' => 'transfer',
        'dicatat_oleh' => $this->admin->id,
    ]);

    // Invoice for Student 2 (Wali 2)
    $inv2 = Invoice::create([
        'no_tagihan' => 'INV/TEST/002',
        'student_id' => $this->student2->id,
        'fee_type_id' => $spp->id,
        'academic_year_id' => $this->year->id,
        'periode' => '2026-08',
        'jumlah' => 350000,
        'jumlah_dibayar' => 0,
        'sisa' => 350000,
        'status' => 'belum_bayar',
        'jatuh_tempo' => '2026-08-10',
        'created_by' => $this->admin->id,
    ]);

    // Wali 1 login
    Sanctum::actingAs($this->userWali1);

    // List invoices: Wali 1 only sees their child's invoice (inv1), NOT inv2
    $resInvoices = $this->getJson('/api/v1/invoices');
    $resInvoices->assertOk();
    expect($resInvoices->json('data'))->toHaveCount(1);
    expect($resInvoices->json('data.0.student_id'))->toBe($this->student1->id);

    // List payments: Wali 1 only sees payments for their child
    $resPayments = $this->getJson('/api/v1/payments');
    $resPayments->assertOk();
    expect($resPayments->json('data'))->toHaveCount(1);
});
