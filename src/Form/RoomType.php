<?php

// Namespace declaration — this form type lives in App\Form
namespace App\Form;

// Import the Room entity (this form creates/edits Room objects)
use App\Entity\Room;
// Import the base form type class
use Symfony\Component\Form\AbstractType;
// Import CallbackTransformer for converting data between form display and entity storage
use Symfony\Component\Form\CallbackTransformer;
// Import specific form field types
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;       // Dropdown select field
use Symfony\Component\Form\Extension\Core\Type\IntegerType;      // Integer input field
use Symfony\Component\Form\Extension\Core\Type\MoneyType;        // Money/currency input field
use Symfony\Component\Form\Extension\Core\Type\NumberType;       // Decimal number input field
use Symfony\Component\Form\Extension\Core\Type\TextareaType;     // Multi-line text input
use Symfony\Component\Form\Extension\Core\Type\TextType;         // Single-line text input
// Import the form builder interface
use Symfony\Component\Form\FormBuilderInterface;
// Import the options resolver
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * RoomType — defines the form for creating/editing hotel rooms (admin panel).
 *
 * This form collects all room properties:
 *   - Name, Type, Price, Capacity, Surface, Bed type
 *   - Status (available/unavailable), Image, Amenities, Description
 *
 * Used in the admin area at /admin/chambres/ajouter and /admin/chambres/{id}/modifier.
 * The form is mapped to the Room entity.
 */
class RoomType extends AbstractType
{
    /**
     * Build the form — define all the fields and their options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Room name field — mapped to Room::name
            ->add('name', TextType::class, [
                'label' => 'Nom de la chambre',
                'attr' => [
                    'placeholder' => 'Ex: Chambre Deluxe avec Vue Mer',
                    'class' => 'form-control',
                ],
            ])
            // Room type dropdown — mapped to Room::type
            // Uses the TYPE_ constants from the Room entity as values
            ->add('type', ChoiceType::class, [
                'label' => 'Type de chambre',
                'choices' => [
                    'Chambre Standard' => Room::TYPE_STANDARD,    // Display label => stored value
                    'Chambre Deluxe' => Room::TYPE_DELUXE,
                    'Suite Drei' => Room::TYPE_SUITE,
                    'Chambre Familiale' => Room::TYPE_FAMILY,
                ],
                'attr' => ['class' => 'form-select'],   // Bootstrap dropdown styling
            ])
            // Price per night field — mapped to Room::pricePerNight
            ->add('pricePerNight', NumberType::class, [
                'label' => 'Prix par nuit (€)',
                'scale' => 2,       // 2 decimal places (e.g. 120.50)
                'attr' => [
                    'placeholder' => '120.00',
                    'class' => 'form-control',
                    'step' => '0.50',   // Allow increments of 0.50€
                ],
            ])
            // Room capacity field — mapped to Room::capacity
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacité (personnes)',
                'attr' => [
                    'min' => 1,        // At least 1 person
                    'max' => 10,       // Maximum 10 people
                    'class' => 'form-control',
                ],
            ])
            // Surface area field — mapped to Room::surface (optional)
            ->add('surface', IntegerType::class, [
                'label' => 'Superficie (m²)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 35',
                    'class' => 'form-control',
                ],
            ])
            // Bed type field — mapped to Room::bedType (optional)
            ->add('bedType', TextType::class, [
                'label' => 'Type de lit',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 1 grand lit King Size',
                    'class' => 'form-control',
                ],
            ])
            // Availability status dropdown — mapped to Room::status
            ->add('status', ChoiceType::class, [
                'label' => 'Statut de disponibilité',
                'choices' => [
                    'Disponible' => Room::STATUS_AVAILABLE,
                    'Indisponible' => Room::STATUS_UNAVAILABLE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            // Room image field — mapped to Room::image (optional)
            // Stores either a local filename or an external URL
            ->add('image', TextType::class, [
                'label' => 'Nom ou URL de l\'image',
                'required' => false,
                'attr' => [
                    'placeholder' => 'deluxe-room.jpg ou URL externe',
                    'class' => 'form-control',
                ],
                'help' => 'Fichier situé dans public/images/rooms/ ou lien image externe',
            ])
            // Amenities field — mapped to Room::amenities
            // Displayed as a comma-separated text field, but stored as a JSON array in the database
            // A data transformer (below) converts between the two formats
            ->add('amenities', TextType::class, [
                'label' => 'Équipements (séparés par des virgules)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Wi-Fi gratuit, Climatisation, Vue mer, Mini-bar, Coffre-fort',
                    'class' => 'form-control',
                ],
                'help' => 'Exemple: Wi-Fi gratuit, Climatisation, Petit-déjeuner inclus',
            ])
            // Room description field — mapped to Room::description
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Présentation complète de la chambre, du confort et des avantages...',
                    'class' => 'form-control',
                ],
            ]);

        // === DATA TRANSFORMER for the "amenities" field ===
        // The Room entity stores amenities as a PHP array (JSON in database),
        // but the form displays them as a comma-separated string.
        // This transformer converts between the two formats:
        //   - Entity → Form: ["Wi-Fi", "Climatisation"] → "Wi-Fi, Climatisation"
        //   - Form → Entity: "Wi-Fi, Climatisation" → ["Wi-Fi", "Climatisation"]
        $builder->get('amenities')
            ->addModelTransformer(new CallbackTransformer(
                // TRANSFORM: Entity array → Form string (for display)
                function ($amenitiesAsArray) {
                    if (is_array($amenitiesAsArray)) {
                        return implode(', ', $amenitiesAsArray);  // Join array elements with ", "
                    }
                    return (string) $amenitiesAsArray;
                },
                // REVERSE TRANSFORM: Form string → Entity array (for storage)
                function ($amenitiesAsString) {
                    if (empty($amenitiesAsString)) {
                        return [];   // Empty string → empty array
                    }
                    // Split by comma and trim whitespace from each item
                    return array_map('trim', explode(',', (string) $amenitiesAsString));
                }
            ));
    }

    /**
     * Configure form options — link this form to the Room entity.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,   // Form data will populate a Room entity
        ]);
    }
}
