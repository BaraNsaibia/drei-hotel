<?php

// Namespace declaration — this form type lives in App\Form
namespace App\Form;

// Import the Reservation entity (this form creates/edits Reservation objects)
use App\Entity\Reservation;
// Import the base form type class
use Symfony\Component\Form\AbstractType;
// Import specific form field types
use Symfony\Component\Form\Extension\Core\Type\DateType;         // Date picker field
use Symfony\Component\Form\Extension\Core\Type\IntegerType;      // Numeric integer input field
use Symfony\Component\Form\Extension\Core\Type\TextareaType;     // Multi-line text input
// Import the form builder interface
use Symfony\Component\Form\FormBuilderInterface;
// Import the options resolver
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ReservationType — defines the reservation booking form.
 *
 * This form is used when a customer books a room (/reserver/{id}).
 * It collects:
 *   - Check-in date (arrival)
 *   - Check-out date (departure)
 *   - Number of guests
 *   - Optional special requests/notes
 *
 * The form is mapped to the Reservation entity.
 */
class ReservationType extends AbstractType
{
    /**
     * Build the form — define all the fields and their options.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Check-in date field — mapped to Reservation::checkIn
            ->add('checkIn', DateType::class, [
                'label' => 'Date d\'arrivée',
                'widget' => 'single_text',       // Render as an HTML5 <input type="date"> (native date picker)
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d'),   // Prevent selecting past dates in the browser
                ],
            ])
            // Check-out date field — mapped to Reservation::checkOut
            ->add('checkOut', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime('+1 day'))->format('Y-m-d'),  // Must be at least tomorrow
                ],
            ])
            // Number of guests field — mapped to Reservation::guests
            ->add('guests', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,     // At least 1 guest
                    'max' => 10,    // Maximum 10 guests (HTML validation)
                ],
            ])
            // Notes/special requests field — mapped to Reservation::notes
            ->add('notes', TextareaType::class, [
                'label' => 'Demandes particulières (Optionnel)',
                'required' => false,    // This field is optional
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,        // Height of the textarea (3 visible lines)
                    'placeholder' => 'Ex: Lit bébé, arrivée tardive, vue souhaitée...',
                ],
            ]);
    }

    /**
     * Configure form options — link this form to the Reservation entity.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,   // Form data will populate a Reservation entity
        ]);
    }
}
