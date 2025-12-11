<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Events\PeminjamanStatusChanged;

class SendPeminjamanStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PeminjamanStatusChanged $event): void
    {
        $peminjaman = $event->peminjaman;
        $newStatus = $event->newStatus;

        Log::info('PeminjamanStatusChanged handled', [
            'peminjaman_id' => $peminjaman->id,
            'old_status' => $event->oldStatus,
            'new_status' => $newStatus,
        ]);

        if ($newStatus === Peminjaman::STATUS_PENDING && $event->oldStatus === null) {
            $this->notifyCreated($peminjaman);

            return;
        }

        $user = $peminjaman->user;

        if (! $user) {
            return;
        }

        switch ($newStatus) {
            case Peminjaman::STATUS_APPROVED:
                $this->notifyApproved($peminjaman);
                break;
            case Peminjaman::STATUS_REJECTED:
                $this->notifyRejected($peminjaman);
                break;
            case Peminjaman::STATUS_CANCELLED:
                $this->notifyCancelled($peminjaman);
                break;
            case Peminjaman::STATUS_PICKED_UP:
                $this->notifyPickedUp($peminjaman);
                break;
            case Peminjaman::STATUS_RETURNED:
                $this->notifyReturned($peminjaman);
                break;
        }
    }

    private function notifyCreated(Peminjaman $peminjaman): void
    {
        $workflows = $peminjaman->approvalWorkflow()
            ->with('approver')
            ->pending()
            ->get();

        Log::info('Peminjaman notifyCreated workflows fetched', [
            'peminjaman_id' => $peminjaman->id,
            'workflow_count' => $workflows->count(),
            'workflow_ids' => $workflows->pluck('id')->all(),
            'approver_ids' => $workflows->pluck('approver_id')->all(),
        ]);

        $approvers = $workflows
            ->pluck('approver')
            ->filter()
            ->unique('id')
            ->values();

        Log::info('Peminjaman notifyCreated approvers resolved', [
            'peminjaman_id' => $peminjaman->id,
            'approver_ids' => $approvers->pluck('id')->all(),
            'approver_statuses' => $approvers->pluck('status')->all(),
        ]);

        if ($approvers->isEmpty()) {
            Log::info('Peminjaman notifyCreated: no approvers found', [
                'peminjaman_id' => $peminjaman->id,
            ]);

            return;
        }

        NotificationBuilder::make()
            ->title('Pengajuan Peminjaman Baru')
            ->message('Ada pengajuan peminjaman "' . $peminjaman->event_name . '" yang menunggu persetujuan Anda.')
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('document-plus')
            ->color('info')
            ->priority('normal')
            ->category('approval')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendToUsers($approvers);
    }

    private function notifyApproved(Peminjaman $peminjaman): void
    {
        $user = $peminjaman->user;

        NotificationBuilder::make()
            ->title('Peminjaman Disetujui')
            ->message('Pengajuan peminjaman "' . $peminjaman->event_name . '" telah disetujui.')
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('check-circle')
            ->color('success')
            ->priority('high')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendTo($user);
    }

    private function notifyRejected(Peminjaman $peminjaman): void
    {
        $user = $peminjaman->user;

        $reason = trim((string) $peminjaman->rejection_reason);
        $message = 'Pengajuan peminjaman "' . $peminjaman->event_name . '" telah ditolak.';

        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }

        NotificationBuilder::make()
            ->title('Peminjaman Ditolak')
            ->message($message)
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('x-circle')
            ->color('danger')
            ->priority('high')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendTo($user);
    }

    private function notifyCancelled(Peminjaman $peminjaman): void
    {
        $user = $peminjaman->user;

        $reason = trim((string) $peminjaman->cancelled_reason);
        $message = 'Pengajuan peminjaman "' . $peminjaman->event_name . '" telah dibatalkan.';

        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }

        NotificationBuilder::make()
            ->title('Peminjaman Dibatalkan')
            ->message($message)
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('x-circle')
            ->color('warning')
            ->priority('normal')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendTo($user);
    }

    private function notifyPickedUp(Peminjaman $peminjaman): void
    {
        $user = $peminjaman->user;

        NotificationBuilder::make()
            ->title('Pengambilan Sarpras Divalidasi')
            ->message('Pengambilan untuk peminjaman "' . $peminjaman->event_name . '" telah divalidasi.')
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('check-circle')
            ->color('info')
            ->priority('normal')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendTo($user);
    }

    private function notifyReturned(Peminjaman $peminjaman): void
    {
        $user = $peminjaman->user;

        NotificationBuilder::make()
            ->title('Pengembalian Sarpras Divalidasi')
            ->message('Pengembalian untuk peminjaman "' . $peminjaman->event_name . '" telah divalidasi dan dinyatakan selesai.')
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('check-circle')
            ->color('success')
            ->priority('normal')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendTo($user);
    }
}
