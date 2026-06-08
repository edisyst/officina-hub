<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 10px; }
  .header { border-bottom: 2px solid #0056b3; margin-bottom: 8px; padding-bottom: 6px; display: flex; justify-content: space-between; }
  .officina { font-size: 9px; color: #666; }
  .codice { font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #0056b3; }
  .qr-area { text-align: center; margin: 8px 0; }
  .qr-area svg { width: 80px; height: 80px; }
  .info-row { margin: 4px 0; }
  .label { font-size: 9px; color: #666; text-transform: uppercase; }
  .value { font-size: 12px; font-weight: bold; }
  .misura { font-size: 16px; font-weight: bold; color: #0056b3; margin: 6px 0; }
  .stagione-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
  .estivo  { background: #ffc107; color: #333; }
  .invernale { background: #17a2b8; color: #fff; }
  .quattro_stagioni { background: #6c757d; color: #fff; }
  .footer { border-top: 1px solid #ccc; margin-top: 8px; padding-top: 4px; font-size: 8px; color: #999; }
  table.dati { width: 100%; border-collapse: collapse; }
  table.dati td { padding: 2px 4px; vertical-align: top; }
  table.dati td.lbl { width: 40%; font-size: 9px; color: #666; }
</style>
</head>
<body>

<div class="header">
  <div>
    <div class="codice">{{ $codice }}</div>
    <div class="officina">{{ setting('officina_nome', '') }}</div>
  </div>
  <div class="qr-area">{!! $qrCodeSvg !!}</div>
</div>

<div style="text-align:center; margin:4px 0">
  <span class="stagione-badge {{ $pneumatico->stagione->value }}">
    {{ $pneumatico->stagione->label() }}
  </span>
  <span class="misura">{{ $pneumatico->misura }}</span>
</div>

<table class="dati">
  <tr>
    <td class="lbl">Marca:</td>
    <td><strong>{{ $pneumatico->marca }}{{ $pneumatico->modello ? ' ' . $pneumatico->modello : '' }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">Veicolo:</td>
    <td><strong>{{ $pneumatico->veicolo?->targa }}</strong> — {{ $pneumatico->veicolo?->marca }} {{ $pneumatico->veicolo?->modello }}</td>
  </tr>
  <tr>
    <td class="lbl">Cliente:</td>
    @php
      $nomeCliente = $pneumatico->cliente?->nome_completo ?? '';
      // Mascheramento: "Rossi M."
      $parti = explode(' ', $nomeCliente);
      $nomeMascherato = count($parti) > 1
        ? $parti[0] . ' ' . strtoupper(substr($parti[1], 0, 1)) . '.'
        : $nomeCliente;
    @endphp
    <td>{{ $nomeMascherato }}</td>
  </tr>
  @if($ultimoDeposito)
  <tr>
    <td class="lbl">Deposito:</td>
    <td>{{ $ultimoDeposito->data_azione->format('d/m/Y') }}</td>
  </tr>
  @if($ultimoDeposito->ubicazione)
  <tr>
    <td class="lbl">Ubicazione:</td>
    <td><strong>{{ $ultimoDeposito->ubicazione }}</strong></td>
  </tr>
  @endif
  @if($ultimoDeposito->usura_percentuale !== null)
  <tr>
    <td class="lbl">Usura:</td>
    <td>{{ $ultimoDeposito->usura_percentuale }}%{{ $ultimoDeposito->usura_note ? ' — ' . $ultimoDeposito->usura_note : '' }}</td>
  </tr>
  @endif
  @endif
  @if($pneumatico->dotati_di_cerchi)
  <tr>
    <td class="lbl">Cerchi:</td>
    <td>{{ $pneumatico->tipo_cerchi ?: 'Sì' }}</td>
  </tr>
  @endif
</table>

<div class="footer">
  {{ $codice }} — Generato il {{ now()->format('d/m/Y') }} — {{ setting('officina_nome', '') }}
</div>

</body>
</html>
