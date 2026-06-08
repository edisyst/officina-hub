<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; }
  .etichetta {
    border: 1px solid #ccc; margin: 8px; padding: 10px;
    width: 200px; height: 140px; display: inline-block;
    vertical-align: top; page-break-inside: avoid;
  }
  .codice { font-size: 13px; font-weight: bold; color: #0056b3; letter-spacing: 1px; }
  .stagione-badge { display: inline-block; padding: 1px 5px; border-radius: 2px; font-size: 8px; font-weight: bold; }
  .estivo  { background: #ffc107; color: #333; }
  .invernale { background: #17a2b8; color: #fff; }
  .quattro_stagioni { background: #6c757d; color: #fff; }
  .misura { font-size: 13px; font-weight: bold; color: #0056b3; }
  .info { font-size: 9px; color: #555; margin: 2px 0; }
  .qr { float: right; }
  .qr svg { width: 55px; height: 55px; }
</style>
</head>
<body>
@foreach($etichette as $e)
<div class="etichetta">
  <div class="qr">{!! $e['qrCodeSvg'] !!}</div>
  <div class="codice">{{ $e['codice'] }}</div>
  <div>
    <span class="stagione-badge {{ $e['pneumatico']->stagione->value }}">{{ $e['pneumatico']->stagione->label() }}</span>
    <span class="misura"> {{ $e['pneumatico']->misura }}</span>
  </div>
  <div class="info"><strong>{{ $e['pneumatico']->marca }}</strong></div>
  <div class="info">Targa: <strong>{{ $e['pneumatico']->veicolo?->targa }}</strong></div>
  @if($e['ultimoDeposito']?->ubicazione)
  <div class="info">Pos: <strong>{{ $e['ultimoDeposito']->ubicazione }}</strong></div>
  @endif
  @if($e['ultimoDeposito'])
  <div class="info">{{ $e['ultimoDeposito']->data_azione->format('d/m/Y') }}</div>
  @endif
</div>
@endforeach
</body>
</html>
