<?php

use App\Mail\BugStatusMail;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function adminContact(): User
{
    config(['crm.require_2fa' => false]);

    return User::create(['name' => 'M', 'email' => 'ac-'.uniqid().'@t.fr', 'password' => bcrypt('x'), 'role' => 'admin']);
}

function clientContact(array $over = []): Prospect
{
    return Prospect::create(array_merge([
        'cle' => 'c-'.uniqid(), 'entreprise' => 'ACME', 'email' => 'principal@acme.fr',
        'est_client' => true, 'client_depuis' => now()->toDateString(),
    ], $over));
}

it('la liste de diffusion = e-mail principal + contacts abonnés, dédupliqués', function () {
    $client = clientContact();
    $client->contacts()->create(['nom' => 'Alice', 'email' => 'alice@acme.fr', 'notifie_tickets' => true]);
    $client->contacts()->create(['nom' => 'Bob', 'email' => 'bob@acme.fr', 'notifie_tickets' => false]);
    $client->contacts()->create(['nom' => 'Doublon', 'email' => 'principal@acme.fr', 'notifie_tickets' => true]);

    $dest = $client->fresh()->destinatairesTickets();

    expect($dest)->toContain('principal@acme.fr')
        ->toContain('alice@acme.fr')
        ->not->toContain('bob@acme.fr')
        ->and($dest)->toHaveCount(2); // principal + alice (doublon fusionné)
});

it('sans e-mail principal, seuls les contacts abonnés reçoivent', function () {
    $client = clientContact(['email' => null]);
    $client->contacts()->create(['email' => 'alice@acme.fr', 'notifie_tickets' => true]);

    expect($client->fresh()->destinatairesTickets())->toBe(['alice@acme.fr']);
});

it('un changement de statut de ticket diffuse à toute la liste', function () {
    Mail::fake();
    $client = clientContact();
    $client->contacts()->create(['email' => 'alice@acme.fr', 'notifie_tickets' => true]);
    $client->contacts()->create(['email' => 'bob@acme.fr', 'notifie_tickets' => false]);
    $projet = $client->projects()->create(['titre' => 'Site', 'statut' => 'en_cours']);
    $bug = $projet->bugs()->create(['titre' => 'X', 'statut' => 'nouveau', 'type' => 'bug', 'gravite' => 'majeur']);

    $this->actingAs(adminContact())->put(route('bugs.update', $bug), ['statut' => 'en_cours'])->assertRedirect();

    Mail::assertSent(BugStatusMail::class, fn ($m) => $m->hasTo('principal@acme.fr') && $m->hasTo('alice@acme.fr'));
    Mail::assertSent(BugStatusMail::class, fn ($m) => ! $m->hasTo('bob@acme.fr'));
});

it('gère les contacts d’un client (ajout, bascule tickets, suppression)', function () {
    $admin = adminContact();
    $client = clientContact();

    $this->actingAs($admin)->post(route('contacts.store', $client), [
        'nom' => 'Alice', 'email' => 'alice@acme.fr', 'fonction' => 'DSI', 'notifie_tickets' => true,
    ])->assertRedirect();

    $contact = $client->contacts()->first();
    expect($contact->email)->toBe('alice@acme.fr')->and($contact->notifie_tickets)->toBeTrue();

    $this->actingAs($admin)->put(route('contacts.update', $contact), ['notifie_tickets' => false])->assertRedirect();
    expect($contact->fresh()->notifie_tickets)->toBeFalse();

    $this->actingAs($admin)->delete(route('contacts.destroy', $contact))->assertRedirect();
    expect($client->contacts()->count())->toBe(0);
});

it('refuse un contact sans e-mail valide', function () {
    $client = clientContact();
    $this->actingAs(adminContact())->post(route('contacts.store', $client), ['nom' => 'X'])
        ->assertSessionHasErrors('email');
});
