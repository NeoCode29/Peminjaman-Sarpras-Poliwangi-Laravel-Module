@php
    $statusLabels = [
        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_PENDING => 'Pending',
        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_APPROVED => 'Disetujui',
        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_REJECTED => 'Ditolak',
        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_PICKED_UP => 'Sedang Dipinjam',
        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_RETURNED => 'Dikembalikan',
        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_CANCELLED => 'Dibatalkan',
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 16px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }
        .subtitle {
            font-size: 11px;
            color: #6B7280;
            margin-bottom: 12px;
        }
        .meta {
            font-size: 11px;
            margin-bottom: 12px;
        }
        .meta span {
            display: inline-block;
            margin-right: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #D1D5DB;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background-color: #F3F4F6;
            font-weight: 600;
            font-size: 11px;
            text-align: left;
        }
        tbody tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 9999px;
            font-size: 10px;
        }
        .badge--pending { background:#FEF3C7; color:#92400E; }
        .badge--approved { background:#D1FAE5; color:#065F46; }
        .badge--rejected { background:#FEE2E2; color:#991B1B; }
        .badge--picked_up { background:#DBEAFE; color:#1D4ED8; }
        .badge--returned { background:#E5E7EB; color:#111827; }
        .badge--cancelled { background:#E5E7EB; color:#4B5563; }
        .summary {
            margin-top: 12px;
            font-size: 11px;
        }
        .summary strong { margin-right: 4px; }
    </style>
</head>
<body>
    <header>
        <h1>Laporan Peminjaman Sarpras</h1>
        <div class="subtitle">Generated at: {{ now()->format('d/m/Y H:i') }}</div>
        <div class="meta">
            <span><strong>Periode:</strong> {{ $filters['start_date'] ?? '-' }} s/d {{ $filters['end_date'] ?? '-' }}</span>
            @if(!empty($filters['status']))
                <span>
                    <strong>Status:</strong>
                    @if($filters['status'] === 'conflicted')
                        Termasuk Konflik
                    @else
                        {{ $statusLabels[$filters['status']] ?? ucfirst($filters['status']) }}
                    @endif
                </span>
            @endif
            @if(!empty($filters['search']))
                <span><strong>Pencarian:</strong> {{ $filters['search'] }}</span>
            @endif
        </div>
    </header>

    <section>
        <table>
            <thead>
                <tr>
                    <th style="width:16%;">Nama Acara</th>
                    <th style="width:14%;">Peminjam</th>
                    <th style="width:14%;">UKM / Unit</th>
                    <th style="width:14%;">Prasarana</th>
                    <th style="width:16%;">Sarana</th>
                    <th style="width:10%;">Lokasi</th>
                    <th style="width:8%;" class="text-center">Peserta</th>
                    <th style="width:8%;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row->event_name }}</strong><br>
                            <span style="font-size:10px;color:#6B7280;">#{{ $row->id }}</span>
                        </td>
                        <td>{{ $row->user->name ?? '-' }}</td>
                        <td>{{ $row->ukm->nama ?? '-' }}</td>
                        <td>{{ $row->prasarana->name ?? '-' }}</td>
                        <td>
                            @php
                                $saranaNames = $row->items
                                    ? $row->items->pluck('sarana.name')->filter()->unique()->implode(', ')
                                    : '';
                            @endphp
                            {{ $saranaNames !== '' ? $saranaNames : '-' }}
                        </td>
                        <td>
                            @if($row->prasarana)
                                {{ $row->prasarana->name }}
                            @elseif($row->lokasi_custom)
                                {{ $row->lokasi_custom }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{ optional($row->start_date)->format('d/m/Y') }}
                            @if($row->end_date && $row->end_date->ne($row->start_date))
                                <br><span style="font-size:10px;color:#6B7280;">s/d {{ optional($row->end_date)->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row->jumlah_peserta ?? '-' }}</td>
                        <td class="text-center">
                            @php
                                $status = $row->status;
                                $label = $statusLabels[$status] ?? ucfirst($status);
                                $badgeClass = 'badge--' . ($status === 'picked_up' ? 'picked_up' : $status);
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data peminjaman pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if(!empty($summary))
        <section class="summary">
            <p>
                <strong>Total Record:</strong> {{ $summary['total_records'] ?? 0 }}
                @if(!empty($summary['total_participants']))
                    | <strong>Total Peserta:</strong> {{ $summary['total_participants'] }}
                @endif
                @if(!empty($summary['total_items_approved']))
                    | <strong>Total Item Disetujui:</strong> {{ $summary['total_items_approved'] }}
                @endif
            </p>
        </section>
    @endif
</body>
</html>
