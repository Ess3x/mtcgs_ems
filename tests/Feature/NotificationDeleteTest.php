<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_only_their_own_notification(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstUser->notify(new SystemNotification('First user', 'Private notice', 'info'));
        $secondUser->notify(new SystemNotification('Second user', 'Keep this notice', 'info'));

        $firstNotificationId = $firstUser->notifications()->firstOrFail()->id;
        $secondNotificationId = $secondUser->notifications()->firstOrFail()->id;

        $this->actingAs($firstUser)
            ->delete(route('notifications.destroy', $firstNotificationId))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $firstNotificationId]);

        $this->delete(route('notifications.destroy', $secondNotificationId))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $secondNotificationId]);
    }
}