<?php

declare(strict_types=1);

/**
 * GENERATED — do not edit by hand.
 * Regenerate: php artisan scp:generate-isolation-tests
 */
namespace Tests\Isolation\Generated;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Platform\Tenancy\Support\TenantContext;
use Platform\Tenancy\Testing\IsolationContext;
use Platform\Tenancy\Testing\IsolationRecordFactory;
use Tests\Feature\PlatformTestCase;

final class SubscriptionIsolationTest extends PlatformTestCase
{
    private const MODEL_CLASS = \Platform\Billing\Models\Subscription::class;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_model_is_listed_in_isolation_manifest(): void
    {
        $manifest = config('tenant-isolation.models', []);

        $this->assertContains(self::MODEL_CLASS, $manifest);
    }

    public function test_model_uses_belongs_to_tenant_trait(): void
    {
        $traits = class_uses_recursive(self::MODEL_CLASS);

        $this->assertContains(
            \Platform\Tenancy\Models\Concerns\BelongsToTenant::class,
            $traits,
        );
    }

    public function test_table_has_tenant_id_column(): void
    {
        $table = (new (self::MODEL_CLASS))->getTable();

        $this->assertTrue(
            Schema::hasColumn($table, 'tenant_id'),
            "Expected {$table}.tenant_id for tenant isolation.",
        );
    }

    public function test_rls_policy_exists_on_postgresql(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS policy checks are PostgreSQL-only.');
        }

        $table = (new (self::MODEL_CLASS))->getTable();

        $result = DB::selectOne(
            'SELECT COUNT(*) AS policy_count FROM pg_policies WHERE tablename = ?',
            [$table],
        );

        $this->assertGreaterThan(
            0,
            (int) ($result->policy_count ?? 0),
            "Expected RLS policy on {$table}.",
        );
    }

    public function test_alpha_cannot_select_beta_row_via_rls(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS isolation checks are PostgreSQL-only.');
        }

        [$alpha, $beta] = IsolationContext::twoTenants('Subscription');
        $betaRecord = (new IsolationRecordFactory)->create(self::MODEL_CLASS, $beta->id);
        $table = $betaRecord->getTable();

        IsolationContext::setRlsTenant($alpha->id);

        $result = DB::selectOne(
            "SELECT COUNT(*) AS row_count FROM {$table} WHERE id = ?",
            [$betaRecord->id],
        );

        $this->assertSame(0, (int) ($result->row_count ?? 0));
    }

    public function test_alpha_cannot_update_beta_row_via_rls(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS isolation checks are PostgreSQL-only.');
        }

        [$alpha, $beta] = IsolationContext::twoTenants('Subscription-upd');
        $betaRecord = (new IsolationRecordFactory)->create(self::MODEL_CLASS, $beta->id);
        $table = $betaRecord->getTable();

        IsolationContext::setRlsTenant($alpha->id);

        $affected = DB::update(
            "UPDATE {$table} SET updated_at = ? WHERE id = ?",
            [now(), $betaRecord->id],
        );

        $this->assertSame(0, $affected);
    }

    public function test_alpha_cannot_delete_beta_row_via_rls(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS isolation checks are PostgreSQL-only.');
        }

        [$alpha, $beta] = IsolationContext::twoTenants('Subscription-del');
        $betaRecord = (new IsolationRecordFactory)->create(self::MODEL_CLASS, $beta->id);
        $table = $betaRecord->getTable();

        IsolationContext::setRlsTenant($alpha->id);

        $affected = DB::delete(
            "DELETE FROM {$table} WHERE id = ?",
            [$betaRecord->id],
        );

        $this->assertSame(0, $affected);
    }

    public function test_alpha_cannot_select_beta_row_via_eloquent_scope(): void
    {
        [$alpha, $beta] = IsolationContext::twoTenants('Subscription-eloq');
        $betaRecord = (new IsolationRecordFactory)->create(self::MODEL_CLASS, $beta->id);

        TenantContext::set($alpha->id);

        $found = (self::MODEL_CLASS)::query()->find($betaRecord->id);

        $this->assertNull($found);
    }
}
