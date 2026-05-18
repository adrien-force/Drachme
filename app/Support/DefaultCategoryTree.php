<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use RuntimeException;

/**
 * Loads the default category tree (Bankin-style) with French display names.
 */
final class DefaultCategoryTree
{
    private const SLUG_UNCATEGORIZED = Category::SLUG_UNCATEGORIZED;

    /**
     * @return list<array{
     *     slug: string,
     *     name: string,
     *     color: string,
     *     icon: string,
     *     is_system?: bool,
     *     children?: list<array<string, mixed>>
     * }>
     */
    public static function definitions(): array
    {
        /** @var list<array<string, mixed>> $raw */
        $raw = json_decode(
            (string) file_get_contents(database_path('data/default_categories.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $definitions = [];
        foreach ($raw as $node) {
            $definitions[] = self::convertNode($node);
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array{
     *     slug: string,
     *     name: string,
     *     color: string,
     *     icon: string,
     *     is_system?: bool,
     *     children?: list<array<string, mixed>>
     * }
     */
    private static function convertNode(array $node): array
    {
        $sourceSlug = (string) $node['name'];
        $slug = $sourceSlug === 'unknown' ? self::SLUG_UNCATEGORIZED : $sourceSlug;

        $entry = [
            'slug' => $slug,
            'name' => self::frenchName($sourceSlug),
            'color' => (string) $node['color'],
            'icon' => (string) $node['icon'],
        ];

        if ($slug === self::SLUG_UNCATEGORIZED || $slug === Category::SLUG_CARD_SETTLEMENT) {
            $entry['is_system'] = true;
        }

        if (($node['is_system'] ?? false) === true) {
            $entry['is_system'] = true;
        }

        $children = [];
        foreach ($node['subcategories'] ?? [] as $sub) {
            if (! is_array($sub)) {
                continue;
            }

            if (($sub['is_custom'] ?? false) === true) {
                continue;
            }

            $children[] = self::convertNode($sub);
        }

        if ($children !== []) {
            $entry['children'] = $children;
        }

        return $entry;
    }

    private static function frenchName(string $slug): string
    {
        $name = self::names()[$slug] ?? null;

        if ($name === null) {
            throw new RuntimeException("Missing French name for category slug [{$slug}].");
        }

        return $name;
    }

    /**
     * @return array<string, string>
     */
    private static function names(): array
    {
        return [
            'personal_and_home_essentials' => 'Maison & personnel',
            'unknown' => 'Non catégorisé',
            'auto_and_transport' => 'Auto & transport',
            'bills_and_utilities' => 'Factures & services',
            'business_and_work' => 'Pro & travail',
            'cash_and_checks' => 'Espèces & chèques',
            'food_and_beverage' => 'Alimentation & boissons',
            'income' => 'Revenus',
            'leasure_and_entertainment' => 'Loisirs & divertissement',
            'investment' => 'Investissement',
            'healthcare' => 'Santé',
            'loan_payments' => 'Remboursements de prêts',
            'refunds' => 'Remboursements',
            'taxes' => 'Impôts',
            'fees' => 'Frais',
            'transfer' => 'Virements',
            'tobacco' => 'Tabac',
            'rent' => 'Loyer',
            'supplies' => 'Fournitures',
            'personal_care' => 'Soins personnels',
            'clothing' => 'Vêtements',
            'education' => 'Éducation',
            'childcare' => 'Garde d\'enfants',
            'student_housing' => 'Logement étudiant',
            'tuition' => 'Frais de scolarité',
            'personal_gifts' => 'Cadeaux personnels',
            'pets' => 'Animaux',
            'fuel' => 'Carburant',
            'repairs' => 'Réparations',
            'public_transport' => 'Transports en commun',
            'taxi' => 'Taxi',
            'parking' => 'Parking',
            'insurance' => 'Assurance',
            'car_rental' => 'Location de voiture',
            'plane_tickets' => 'Billets d\'avion',
            'train_tickets' => 'Billets de train',
            'tolls' => 'Péages',
            'car_wash' => 'Lavage auto',
            'electricity' => 'Électricité',
            'water' => 'Eau',
            'internet' => 'Internet',
            'mobile_phone' => 'Téléphone mobile',
            'heating' => 'Chauffage',
            'trash_and_recycling' => 'Déchets & recyclage',
            'bills_subscriptions' => 'Abonnements',
            'office_supplies' => 'Fournitures de bureau',
            'professional_services' => 'Services professionnels',
            'business_travel' => 'Voyages professionnels',
            'advertising' => 'Publicité',
            'freelancing' => 'Freelance',
            'accounting' => 'Comptabilité',
            'printing' => 'Impression',
            'shipping' => 'Expédition',
            'salary' => 'Salaire',
            'marketing' => 'Marketing',
            'online_services' => 'Services en ligne',
            'legal_assistance' => 'Assistance juridique',
            'financial_advising' => 'Conseil financier',
            'withdrawal' => 'Retrait',
            'check_deposit' => 'Dépôt de chèque',
            'cash_deposit' => 'Dépôt d\'espèces',
            'groceries' => 'Courses',
            'dining_out' => 'Restaurant',
            'fast_food' => 'Restauration rapide',
            'alcohol_and_bars' => 'Alcool & bars',
            'coffee_shop' => 'Café',
            'snacks' => 'Snacks',
            'salary_income' => 'Salaire',
            'bonuses' => 'Primes',
            'investments' => 'Placements',
            'gift' => 'Cadeau reçu',
            'rental_income' => 'Revenus locatifs',
            'movie_and_theater' => 'Cinéma & théâtre',
            'sports_and_recreation' => 'Sport & loisirs',
            'hobbies' => 'Loisirs créatifs',
            'subscriptions' => 'Abonnements',
            'vacations' => 'Vacances',
            'art_and_museum' => 'Art & musées',
            'bonds' => 'Obligations',
            'stocks' => 'Actions',
            'real_estates' => 'Immobilier',
            'retirement' => 'Retraite',
            'cryptos' => 'Cryptomonnaies',
            'safety_net' => 'Épargne de précaution',
            'doctor_visit' => 'Médecin',
            'dentist_visit' => 'Dentiste',
            'medication' => 'Médicaments',
            'health_insurance' => 'Mutuelle',
            'medical_equipment' => 'Équipement médical',
            'optician' => 'Opticien',
            'product_returns' => 'Retours produits',
            'tax_refunds' => 'Remboursements d\'impôts',
            'warranty_claims' => 'Garanties',
            'insurance_payouts' => 'Indemnisations',
            'income_taxes' => 'Impôt sur le revenu',
            'vat' => 'TVA',
            'property_taxes' => 'Taxe foncière',
            'capital_gain_taxes' => 'Plus-values',
            'business_taxes' => 'Impôts professionnels',
            'social_contributions' => 'Cotisations sociales',
            'bank_fees' => 'Frais bancaires',
            'late_fees' => 'Pénalités de retard',
            'service_charge' => 'Frais de service',
            'internal_transfer' => 'Virement interne',
            'card_settlement' => 'Règlement carte',
            'external_transfer' => 'Virement externe',
            'gifts_transfer' => 'Cadeaux envoyés',
            'bill_payment' => 'Paiement de facture',
            'international_transfer' => 'Virement international',
        ];
    }
}
