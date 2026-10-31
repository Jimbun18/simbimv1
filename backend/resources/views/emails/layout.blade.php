<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'Pemberitahuan SIM-BIM SDI Bina Insan Mandiri' }}</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f1f5f9;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #1e293b;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      width: 100%;
      background-color: #f1f5f9;
      padding: 32px 16px;
      box-sizing: border-box;
    }
    .card {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
      border: 1px solid #e2e8f0;
    }
    .header {
      background: linear-gradient(135deg, #064e3b 0%, #065f46 60%, #047857 100%);
      padding: 28px 24px;
      text-align: center;
      color: #ffffff;
    }
    .header h1 {
      margin: 0;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    .header p {
      margin: 4px 0 0;
      font-size: 13px;
      color: #a7f3d0;
      font-weight: 500;
    }
    .content {
      padding: 32px 24px;
      line-height: 1.6;
      font-size: 14px;
    }
    .btn {
      display: inline-block;
      background-color: #059669;
      color: #ffffff !important;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      text-align: center;
      margin: 20px 0;
      box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);
    }
    .btn:hover {
      background-color: #047857;
    }
    .callout {
      background-color: #f0fdf4;
      border-left: 4px solid #10b981;
      padding: 14px 16px;
      border-radius: 6px;
      margin: 20px 0;
      font-size: 13px;
      color: #065f46;
    }
    .footer {
      background-color: #f8fafc;
      padding: 24px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      border-top: 1px solid #e2e8f0;
    }
    .footer p {
      margin: 4px 0;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <h1>SIM-BIM</h1>
        <p>SDI Bina Insan Mandiri Purwokerto</p>
      </div>
      <div class="content">
        @yield('content')
      </div>
      <div class="footer">
        <p><strong>SDI Bina Insan Mandiri Purwokerto</strong></p>
        <p>Jl. Sunan Kalijaga No. 18, Purwokerto, Banyumas, Jawa Tengah</p>
        <p style="margin-top: 8px; color: #94a3b8; font-size: 11px;">
          Email ini dikirim secara otomatis oleh Sistem Informasi SIM-BIM. Mohon tidak membalas email ini secara langsung.
        </p>
      </div>
    </div>
  </div>
</body>
</html>

