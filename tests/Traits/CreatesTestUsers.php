<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

trait CreatesTestUsers
{
    /**
     * Create a user with specific role
     *
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createUserWithRole(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create an admin user
     *
     * @param array $attributes
     * @return User
     */
    protected function createAdminUser(array $attributes = []): User
    {
        return $this->createUserWithRole('admin', $attributes);
    }

    /**
     * Create an editor user
     *
     * @param array $attributes
     * @return User
     */
    protected function createEditorUser(array $attributes = []): User
    {
        return $this->createUserWithRole('editor', $attributes);
    }

    /**
     * Create a primary responder user
     *
     * @param array $attributes
     * @return User
     */
    protected function createPrimaryResponderUser(array $attributes = []): User
    {
        return $this->createUserWithRole('primary_responder', $attributes);
    }

    /**
     * Create an approver user
     *
     * @param array $attributes
     * @return User
     */
    protected function createApproverUser(array $attributes = []): User
    {
        return $this->createUserWithRole('approver', $attributes);
    }

    /**
     * Create a viewer user
     *
     * @param array $attributes
     * @return User
     */
    protected function createViewerUser(array $attributes = []): User
    {
        return $this->createUserWithRole('viewer', $attributes);
    }

    /**
     * Create a viewer user with access scope restrictions
     *
     * @param array $accessScope
     * @param array $attributes
     * @return User
     */
    protected function createRestrictedViewerUser(array $accessScope, array $attributes = []): User
    {
        return $this->createUserWithRole('viewer', array_merge([
            'access_scope' => $accessScope,
        ], $attributes));
    }

    /**
     * Create a user with specific department
     *
     * @param string $department
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createUserWithDepartment(string $department, string $role = 'editor', array $attributes = []): User
    {
        return $this->createUserWithRole($role, array_merge([
            'department' => $department,
        ], $attributes));
    }

    /**
     * Create a land affairs user
     *
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createLandAffairsUser(string $role = 'editor', array $attributes = []): User
    {
        return $this->createUserWithDepartment('land_affairs', $role, $attributes);
    }

    /**
     * Create an accounting user
     *
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createAccountingUser(string $role = 'editor', array $attributes = []): User
    {
        return $this->createUserWithDepartment('accounting', $role, $attributes);
    }

    /**
     * Create a construction planning user
     *
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createConstructionPlanningUser(string $role = 'editor', array $attributes = []): User
    {
        return $this->createUserWithDepartment('construction_planning', $role, $attributes);
    }

    /**
     * Create multiple users with different roles
     *
     * @param array $roles
     * @param array $commonAttributes
     * @return Collection
     */
    protected function createUsersWithRoles(array $roles, array $commonAttributes = []): Collection
    {
        $users = collect();

        foreach ($roles as $role) {
            $users->push($this->createUserWithRole($role, $commonAttributes));
        }

        return $users;
    }

    /**
     * Create a complete set of users for testing (one of each role)
     *
     * @param array $commonAttributes
     * @return array
     */
    protected function createCompleteUserSet(array $commonAttributes = []): array
    {
        return [
            'admin' => $this->createAdminUser($commonAttributes),
            'editor' => $this->createEditorUser($commonAttributes),
            'primary_responder' => $this->createPrimaryResponderUser($commonAttributes),
            'approver' => $this->createApproverUser($commonAttributes),
            'viewer' => $this->createViewerUser($commonAttributes),
        ];
    }

    /**
     * Create an inactive user
     *
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createInactiveUser(string $role = 'viewer', array $attributes = []): User
    {
        return $this->createUserWithRole($role, array_merge([
            'is_active' => false,
        ], $attributes));
    }

    /**
     * Create a user with multiple departments
     *
     * @param array $departments
     * @param string $role
     * @param array $attributes
     * @return User
     */
    protected function createUserWithMultipleDepartments(array $departments, string $role = 'editor', array $attributes = []): User
    {
        return $this->createUserWithRole($role, array_merge([
            'department' => implode(', ', $departments),
        ], $attributes));
    }

    /**
     * Authenticate as a specific user
     *
     * @param User|null $user
     * @return User
     */
    protected function actingAsUser(?User $user = null): User
    {
        if (!$user) {
            $user = $this->createEditorUser();
        }

        $this->actingAs($user);

        return $user;
    }

    /**
     * Authenticate as admin user
     *
     * @param array $attributes
     * @return User
     */
    protected function actingAsAdmin(array $attributes = []): User
    {
        $admin = $this->createAdminUser($attributes);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Authenticate as editor user
     *
     * @param array $attributes
     * @return User
     */
    protected function actingAsEditor(array $attributes = []): User
    {
        $editor = $this->createEditorUser($attributes);
        $this->actingAs($editor);

        return $editor;
    }

    /**
     * Authenticate as viewer user
     *
     * @param array $attributes
     * @return User
     */
    protected function actingAsViewer(array $attributes = []): User
    {
        $viewer = $this->createViewerUser($attributes);
        $this->actingAs($viewer);

        return $viewer;
    }
}
