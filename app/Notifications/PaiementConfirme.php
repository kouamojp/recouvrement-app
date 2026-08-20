<?php

namespace App\Notifications;

use App\Models\Debiteur;
use App\Models\Dette;
use App\Models\Paiement;
use App\Support\Montants;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Annonce d'un règlement en ligne encaissé.
 *
 * Le même message part à l'administration, au partenaire créancier et à
 * l'agent : seule l'accroche change, chacun n'ayant pas la même raison d'être
 * prévenu. Les montants cités sont ceux d'après imputation.
 */
class PaiementConfirme extends Notification
{
    use Queueable;

    /** @var Paiement */
    protected $paiement;

    /** @var Dette|null */
    protected $dette;

    /** @var Debiteur|null */
    protected $debiteur;

    /** @var string admin, partenaire ou agent. */
    protected $role;

    public function __construct(Paiement $paiement, Dette $dette = null, Debiteur $debiteur = null, $role = 'admin')
    {
        $this->paiement = $paiement;
        $this->dette = $dette;
        $this->debiteur = $debiteur;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $devise = ' ' . $this->paiement->devise;
        $societe = $this->debiteur ? $this->debiteur->societe_debitrice : 'Un débiteur';
        $intitule = $this->dette ? $this->dette->intitule : 'une dette';

        $message = (new MailMessage())
            ->subject('Paiement reçu — ' . $this->montant($this->paiement->montant) . $devise
                . ' (' . $this->paiement->reference . ')')
            ->greeting('Bonjour,')
            ->line($this->accroche($societe, $intitule))
            ->line('Montant réglé : **' . $this->montant($this->paiement->montant) . $devise . '**')
            ->line('Moyen de paiement : ' . Paiement::libelleMoyen($this->paiement->moyen))
            ->line('Référence : ' . $this->paiement->reference);

        if ($this->dette) {
            $message->line('Total versé sur cette dette : '
                    . $this->montant($this->dette->montant_verse) . $devise)
                ->line('Solde restant dû : '
                    . $this->montant(Montants::solde($this->dette)) . $devise);
        }

        return $message->salutation('— ' . config('app.name'));
    }

    protected function accroche($societe, $intitule)
    {
        if ($this->role === 'partenaire') {
            return $societe . ' vient de régler en ligne une créance que vous nous avez confiée ('
                . $intitule . ').';
        }

        if ($this->role === 'agent') {
            return $societe . ', dossier dont vous avez la charge, vient de régler en ligne : '
                . $intitule . '.';
        }

        return $societe . ' vient d\'effectuer un paiement en ligne sur ' . $intitule . '.';
    }

    /** Séparateur d'espace pour les milliers, comme dans le tableau de bord. */
    protected function montant($valeur)
    {
        return number_format(Montants::versEntier($valeur), 0, ',', ' ');
    }
}
