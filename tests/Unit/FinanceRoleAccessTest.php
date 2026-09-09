<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class FinanceRoleAccessTest extends \Tests\TestCase
{
    #[Test]
    public function finance_head_is_recognized_as_a_finance_role(): void
    {
        $user = new User(['role' => 'finance_head']);

        $this->assertTrue($user->isFinanceHead());
        $this->assertTrue($user->isFinanceStaff());
    }
}
