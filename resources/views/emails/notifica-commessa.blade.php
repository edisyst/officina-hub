<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: #343a40; color: #fff; padding: 16px 20px; border-radius: 4px 4px 0 0; }
    .header h1 { margin: 0; font-size: 18px; }
    .body { background: #fff; border: 1px solid #dee2e6; border-top: none; padding: 24px; }
    .body pre { white-space: pre-wrap; font-family: inherit; font-size: 14px; line-height: 1.6; margin: 0; }
    .footer { font-size: 11px; color: #6c757d; text-align: center; margin-top: 16px; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      @php $nomeOfficina = setting('officina_nome', config('app.name')); @endphp
      @if(file_exists(public_path('images/logo.png')))
        <img src="{{ public_path('images/logo.png') }}" alt="{{ $nomeOfficina }}" style="max-height:40px; vertical-align:middle; margin-right:10px;">
      @endif
      <h1 style="display:inline; vertical-align:middle;">{{ $nomeOfficina }}</h1>
    </div>
    <div class="body">
      <pre>{{ $corpo }}</pre>
    </div>
    <div class="footer">
      Questo messaggio è stato inviato automaticamente da Officina Hub. Non rispondere a questa email.
    </div>
  </div>
</body>
</html>
