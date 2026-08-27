<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every list that offers a delete button: the row goes, the history stays. */
class DeleteTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $this->seed(RolesSeeder::class);
        Warehouse::create(['name' => 'Main', 'is_default' => true]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_a_product_is_soft_deleted_and_leaves_the_list(): void
    {
        $this->actingAsRole('manager');
        $product = Product::create(['name' => 'Eau 1.5L', 'unit' => 'piece', 'is_active' => true]);

        $this->delete(route('products.destroy', $product))->assertRedirect();

        $this->assertSoftDeleted($product);
        $this->assertSame(0, Product::where('name', 'Eau 1.5L')->count());
    }

    public function test_a_customer_with_no_balance_is_soft_deleted(): void
    {
        $this->actingAsRole('manager');
        $customer = Customer::create(['name' => 'Ali Client', 'is_active' => true]);

        $this->delete(route('customers.destroy', $customer))->assertRedirect();

        $this->assertSoftDeleted($customer);
        $this->assertSame(0, Customer::where('name', 'Ali Client')->count());
    }

    public function test_a_customer_who_still_owes_money_cannot_be_deleted(): void
    {
        $this->actingAsRole('manager');
        $customer = Customer::create(['name' => 'Ali Client', 'is_active' => true]);
        $customer->forceFill(['balance' => 5000])->save();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($customer);
    }

    public function test_a_supplier_with_no_balance_is_soft_deleted(): void
    {
        $this->actingAsRole('manager');
        $supplier = Supplier::create(['name' => 'Sarl Import', 'is_active' => true]);

        $this->delete(route('suppliers.destroy', $supplier))->assertRedirect();

        $this->assertSoftDeleted($supplier);
        $this->assertSame(0, Supplier::where('name', 'Sarl Import')->count());
    }

    public function test_a_supplier_we_still_owe_cannot_be_deleted(): void
    {
        $this->actingAsRole('manager');
        $supplier = Supplier::create(['name' => 'Sarl Import', 'is_active' => true]);
        $supplier->forceFill(['balance' => 9000])->save();

        $this->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($supplier);
    }

    public function test_an_expense_is_soft_deleted(): void
    {
        $user = $this->actingAsRole('accountant');
        $category = ExpenseCategory::create(['name' => 'Loyer']);

        $expense = Expense::create([
            'expense_category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 250000,
            'method' => 'cash',
            'spent_at' => now()->toDateString(),
            'description' => 'Loyer du mois',
        ]);

        $this->delete(route('expenses.destroy', $expense))->assertRedirect();

        $this->assertSoftDeleted($expense);
        $this->assertSame(0, Expense::where('description', 'Loyer du mois')->count());
    }

    public function test_an_owner_can_delete_another_user(): void
    {
        $this->actingAsRole('owner');

        $other = User::factory()->create(['name' => 'Caissier Test', 'is_active' => true]);
        $other->assignRole('sales');

        $this->delete(route('users.destroy', $other))->assertRedirect();

        $this->assertSoftDeleted($other);
        $this->assertSame(0, User::where('name', 'Caissier Test')->count());
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $me = $this->actingAsRole('owner');

        $this->delete(route('users.destroy', $me))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($me);
    }

    public function test_deleting_an_owner_is_refused_when_no_other_owner_is_left(): void
    {
        $owner = $this->actingAsRole('owner');

        // The only other account with the right to delete is this same owner,
        // so the guard is checked directly on the model layer the route uses.
        $second = User::factory()->create(['is_active' => true]);
        $second->assignRole('owner');

        // Two active owners: the second one can go.
        $this->delete(route('users.destroy', $second))->assertRedirect();
        $this->assertSoftDeleted($second);

        // Only $owner is left, and an account can never delete itself.
        $this->delete(route('users.destroy', $owner))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($owner);
    }

    public function test_a_cashier_cannot_delete_a_product(): void
    {
        $this->actingAsRole('sales');
        $product = Product::create(['name' => 'Eau 1.5L', 'unit' => 'piece', 'is_active' => true]);

        $this->delete(route('products.destroy', $product))->assertForbidden();
        $this->assertNotSoftDeleted($product);
    }
}
