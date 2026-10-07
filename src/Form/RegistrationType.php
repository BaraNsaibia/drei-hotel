<?php

// Namespace declaration — this form type lives in App\Form
namespace App\Form;

// Import the User entity (this form creates/edits User objects)
use App\Entity\User;
// Import the base form type class
use Symfony\Component\Form\AbstractType;
// Import specific form field types
use Symfony\Component\Form\Extension\Core\Type\EmailType;       // Email input field
use Symfony\Component\Form\Extension\Core\Type\PasswordType;    // Password input field (hidden chars)
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;    // Two matching fields (password + confirm)
use Symfony\Component\Form\Extension\Core\Type\TelType;         // Phone number input field
use Symfony\Component\Form\Extension\Core\Type\TextType;        // Regular text input field
// Import the form builder interface
use Symfony\Component\Form\FormBuilderInterface;
// Import the options resolver for form configuration
use Symfony\Component\OptionsResolver\OptionsResolver;
// Import validation constraints (applied directly on form fields, not on the entity)
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * RegistrationType — defines the user registration form.
 *
 * This form is used on the /inscription page to collect:
 *   - First name, Last name
 *   - Email address
 *   - Phone number
 *   - Password (entered twice for confirmation)
 *
 * The form is mapped to the User entity, meaning submitted data
 * is automatically transferred to a User object.
 */
class RegistrationType extends AbstractType
{
    /**
     * Build the form — define all the fields and their options.
     *
     * @param FormBuilderInterface $builder The form builder used to add fields
     * @param array $options Additional options passed to the form
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // First name field — mapped to User::firstName
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',                                // French label displayed above the field
                'attr' => [
                    'placeholder' => 'Ex: Karim',                   // Placeholder text inside the field
                    'class' => 'form-control',                      // Bootstrap CSS class for styling
                ],
            ])
            // Last name field — mapped to User::lastName
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Ex: Ben Salah',
                    'class' => 'form-control',
                ],
            ])
            // Email field — mapped to User::email
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => [
                    'placeholder' => 'exemple@email.com',
                    'class' => 'form-control',
                ],
            ])
            // Phone number field — mapped to User::phone
            ->add('phone', TelType::class, [
                'label' => 'Numéro de Téléphone',
                'attr' => [
                    'placeholder' => '+33 1 23 45 67 89',
                    'class' => 'form-control',
                ],
            ])
            // Password field — NOT mapped to the entity (mapped: false)
            // This means Symfony will NOT try to call $user->setPlainPassword()
            // Instead, the controller reads it manually with $form->get('plainPassword')->getData()
            // and hashes it before storing it
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,           // Each sub-field is a password input
                'mapped' => false,                        // NOT linked to any User property
                'invalid_message' => 'Les mots de passe doivent être identiques.',  // Error if passwords don't match
                'options' => ['attr' => ['class' => 'form-control']],
                'required' => true,
                // First password field (the main one)
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr' => ['placeholder' => 'Minimum 6 caractères', 'class' => 'form-control'],
                ],
                // Second password field (confirmation — must match the first)
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => ['placeholder' => 'Répétez votre mot de passe', 'class' => 'form-control'],
                ],
                // Validation constraints applied to this field
                'constraints' => [
                    // Password cannot be empty
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe.',
                    ]),
                    // Password must be between 6 and 4096 characters
                    // (4096 is a security limit to prevent extremely long password attacks)
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    /**
     * Configure form options — link this form to the User entity.
     * This tells Symfony to automatically fill a User object with form data.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,   // Form data will populate a User entity
        ]);
    }
}
