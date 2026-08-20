<?php

namespace App\Support;

/**
 * Utilitaires de calcul sur les dettes.
 *
 * Les montants sont stockés en base sous forme de chaînes, parfois saisies avec
 * des espaces ou des séparateurs de milliers. Tout passe donc par versEntier()
 * avant le moindre calcul.
 */
class Montants
{
    /** Devise unique de l'application. */
    const DEVISE = 'FCFA';

    /**
     * Convertit un montant stocké en entier, en ignorant tout ce qui n'est ni
     * un chiffre ni un signe négatif.
     */
    public static function versEntier($valeur)
    {
        if ($valeur === null || $valeur === '') {
            return 0;
        }

        if (is_int($valeur) || is_float($valeur)) {
            return (int) $valeur;
        }

        return (int) preg_replace('/[^0-9-]/', '', (string) $valeur);
    }

    /**
     * Solde d'une dette.
     *
     * La valeur enregistrée par le modèle fait foi ; elle n'est recalculée que
     * si elle est absente, pour ne pas diverger de ce qu'affiche l'admin.
     */
    public static function solde($dette)
    {
        $enregistre = $dette->getAttributes()['solde'] ?? null;

        if ($enregistre !== null && $enregistre !== '') {
            return self::versEntier($enregistre);
        }

        return self::versEntier($dette->montant_reconnu) - self::versEntier($dette->montant_verse);
    }

    /**
     * Agrège un ensemble de dettes en indicateurs prêts à afficher.
     */
    public static function synthese($dettes)
    {
        $reclame = 0;
        $reconnu = 0;
        $verse = 0;
        $solde = 0;
        $nombre = 0;

        foreach ($dettes as $dette) {
            $nombre++;
            $reclame += self::versEntier($dette->montant_reclame);
            $reconnu += self::versEntier($dette->montant_reconnu);
            $verse += self::versEntier($dette->montant_verse);
            $solde += self::solde($dette);
        }

        return [
            'nombre_dettes'     => $nombre,
            'montant_reclame'   => $reclame,
            'montant_reconnu'   => $reconnu,
            'montant_verse'     => $verse,
            'solde'             => $solde,
            // Part du montant reconnu effectivement recouvrée, en pourcentage.
            'taux_recouvrement' => $reconnu > 0 ? round($verse / $reconnu * 100, 2) : 0.0,
            'devise'            => self::DEVISE,
        ];
    }
}
