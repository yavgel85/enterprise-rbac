<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class ContactPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::ContactsView);
    }

    public function view(User $user, Contact $contact): Response
    {
        return $this->auth->allows($user, Permission::ContactsView, resource: $contact);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::ContactsCreate);
    }

    public function update(User $user, Contact $contact): Response
    {
        return $this->auth->allows($user, Permission::ContactsUpdate, resource: $contact);
    }

    public function delete(User $user, Contact $contact): Response
    {
        return $this->auth->allows($user, Permission::ContactsDelete, resource: $contact);
    }
}
