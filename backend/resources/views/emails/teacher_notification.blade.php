@extends('emails.layout')

@section('content')
  <h2 style="margin-top: 0; color: #0f172a; font-size: 18px; font-weight: 700;">
    {{ $title }}
  </h2>

  <p>
    Assalamu'alaikum Wr. Wb.,<br>
    Yth. <strong>{{ $recipientName }}</strong>,
  </p>

  <p style="color: #334155;">
    {{ $bodyMessage }}
  </p>

  @if(!empty($extraDetails))
    <div class="callout">
      @foreach($extraDetails as $label => $val)
        <div style="margin-bottom: 6px;">
          <strong>{{ $label }}:</strong> {{ $val }}
        </div>
      @endforeach
    </div>
  @endif

  @if(!empty($actionUrl))
    <div style="text-align: center; margin: 28px 0 16px;">
      <a href="{{ $actionUrl }}" class="btn" target="_blank">
        {{ $actionText ?? 'Buka di SIM-BIM' }}
      </a>
    </div>
  @endif

  <p style="margin-top: 24px; color: #475569; font-size: 13px;">
    Wassalamu'alaikum Wr. Wb.,<br>
    <em>Tim Administrasi & Bimbingan SIM-BIM</em>
  </p>
@endsection

