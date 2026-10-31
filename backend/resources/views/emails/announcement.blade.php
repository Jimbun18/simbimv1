@extends('emails.layout')

@section('content')
  <div style="margin-bottom: 16px;">
    <span style="display: inline-block; padding: 4px 12px; background-color: #d1fae5; color: #065f46; font-size: 11px; font-weight: 800; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px;">
      Pengumuman Resmi • {{ ucfirst($kategori ?? 'Umum') }}
    </span>
  </div>

  <h2 style="margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 800; line-height: 1.3;">
    {{ $judul }}
  </h2>

  <p style="color: #64748b; font-size: 12px; margin-top: -6px; margin-bottom: 20px;">
    Diterbitkan pada: <strong>{{ $tglTerbit }}</strong> oleh <strong>{{ $publisherName }}</strong>
  </p>

  <p>
    Assalamu'alaikum Wr. Wb.,<br>
    Yth. Bapak/Ibu <strong>{{ $recipientName }}</strong>,
  </p>

  <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0; color: #334155; line-height: 1.7; font-size: 14px; white-space: pre-line;">{!! nl2br(e($isi)) !!}</div>

  @if(!empty($lampiranUrl))
    <div class="callout" style="background-color: #f0fdf4; border-left: 4px solid #10b981;">
      <strong>Lampiran Dokumen:</strong><br>
      <a href="{{ $lampiranUrl }}" target="_blank" style="color: #059669; font-weight: bold; text-decoration: underline;">
        Unduh / Lihat Berkas Lampiran
      </a>
    </div>
  @endif

  @if(!empty($actionUrl))
    <div style="text-align: center; margin: 28px 0 16px;">
      <a href="{{ $actionUrl }}" class="btn" target="_blank">
        Buka di Aplikasi SIM-BIM
      </a>
    </div>
  @endif

  <p style="margin-top: 24px; color: #475569; font-size: 13px;">
    Wassalamu'alaikum Wr. Wb.,<br>
    <em>Pimpinan & Tata Usaha SDI Bina Insan Mandiri</em>
  </p>
@endsection

