<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_sends_an_email_to_the_configured_support_address(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.submit'), [
            'name' => 'Jane Player',
            'email' => 'jane@example.com',
            'subject' => 'Question about tournaments',
            'message' => 'How do I host my own tournament?',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // ContactMessageMail implements ShouldQueue, so it's queued rather
        // than sent synchronously — assert against the queued mailable.
        Mail::assertQueued(ContactMessageMail::class, function (ContactMessageMail $mail) {
            return $mail->senderEmail === 'jane@example.com'
                && $mail->messageSubject === 'Question about tournaments';
        });
    }

    public function test_contact_form_requires_all_fields(): void
    {
        $response = $this->post(route('contact.submit'), []);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }
}
