<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Debiteur;
use App\Models\Partenaire;
use Illuminate\Console\Command;

/**
 * Migre vers bcrypt les mots de passe historiquement stockés en clair.
 *
 * La commande est idempotente : un mot de passe déjà haché est reconnu à son
 * préfixe bcrypt et laissé intact, on peut donc la relancer sans risque.
 */
class HashLegacyPasswords extends Command
{
    protected $signature = 'passwords:hash
                            {--dry-run : Affiche ce qui serait modifié sans rien enregistrer}';

    protected $description = 'Hache en bcrypt les mots de passe des débiteurs, partenaires et agents encore stockés en clair';

    /**
     * Les collections concernées, libellées pour le rapport.
     */
    protected $cibles = [
        'Débiteurs'   => Debiteur::class,
        'Partenaires' => Partenaire::class,
        'Agents'      => Agent::class,
    ];

    public function handle()
    {
        $simulation = $this->option('dry-run');

        if ($simulation) {
            $this->warn('Mode simulation : aucune écriture ne sera effectuée.');
            $this->line('');
        }

        $totalHaches = 0;
        $totalSansMotDePasse = 0;

        foreach ($this->cibles as $libelle => $modele) {
            $haches = 0;
            $dejaHaches = 0;
            $sansMotDePasse = 0;

            foreach ($modele::all() as $enregistrement) {
                $motDePasse = $enregistrement->getAttributes()['password'] ?? null;

                if ($motDePasse === null || $motDePasse === '') {
                    $sansMotDePasse++;
                    continue;
                }

                if (preg_match('/^\$2[aby]\$/', $motDePasse)) {
                    $dejaHaches++;
                    continue;
                }

                if (! $simulation) {
                    // Le mutateur du modèle se charge du hachage.
                    $enregistrement->password = $motDePasse;
                    $enregistrement->save();
                }

                $haches++;
            }

            $this->info(sprintf(
                '%-12s %d haché(s), %d déjà haché(s), %d sans mot de passe',
                $libelle . ' :',
                $haches,
                $dejaHaches,
                $sansMotDePasse
            ));

            $totalHaches += $haches;
            $totalSansMotDePasse += $sansMotDePasse;
        }

        $this->line('');

        if ($totalHaches === 0) {
            $this->info('Aucun mot de passe en clair trouvé.');
        } elseif ($simulation) {
            $this->warn($totalHaches . ' mot(s) de passe seraient hachés. Relancez sans --dry-run pour appliquer.');
        } else {
            $this->info($totalHaches . ' mot(s) de passe hachés.');
            $this->warn('Tout identifiant déjà communiqué doit être considéré comme compromis et renouvelé.');
        }

        if ($totalSansMotDePasse > 0) {
            $this->line('');
            $this->warn($totalSansMotDePasse . ' compte(s) sans mot de passe : ils ne pourront pas se connecter tant qu\'un mot de passe ne leur aura pas été défini via Backpack.');
        }

        return 0;
    }
}
