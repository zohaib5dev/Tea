<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_serves_the_vue_app_shell(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('/frontend/assets/', false);
    }

    public function test_csrf_bootstrap_endpoint_returns_a_session_token(): void
    {
        $this->getJson('/api/csrf-cookie')->assertOk()->assertJsonStructure(['token']);
    }

    public function test_every_table_has_a_unique_uuid_column(): void
    {
        $tables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'posts',
            'post_reactions',
            'post_comments',
            'friendships',
            'conversations',
            'conversation_participants',
            'messages',
            'message_deletions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'uuid'), "{$table} is missing its UUID column.");
            $this->assertTrue(Schema::hasIndex($table, ['uuid'], 'unique'), "{$table}.uuid must be unique.");
        }
    }

    public function test_upgrade_migration_assigns_uuids_to_existing_rows(): void
    {
        $user = User::factory()->create();

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropUnique('posts_uuid_unique');
            $table->dropColumn('uuid');
        });

        $postId = DB::table('posts')->insertGetId([
            'user_id' => $user->id,
            'body' => 'Legacy post',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_30_000002_add_uuids_to_existing_tables.php');
        $migration->up();

        $uuid = DB::table('posts')->where('id', $postId)->value('uuid');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
        $this->assertTrue(Schema::hasIndex('posts', ['uuid'], 'unique'));
    }

    public function test_registration_returns_a_user_and_authenticates_the_session(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Aio User',
            'email' => 'aio@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()
            ->assertJsonPath('user.name', 'Aio User');

        $this->getJson('/api/user')->assertOk()
            ->assertJsonPath('user.email', 'aio@example.com');
    }

    public function test_login_authenticates_with_valid_credentials_and_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertUnprocessable();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_feed_only_returns_posts_visible_to_the_signed_in_user(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        Post::create(['user_id' => $viewer->id, 'body' => 'My post', 'visibility' => 'private']);
        Post::create(['user_id' => $author->id, 'body' => 'Public post', 'visibility' => 'public']);
        Post::create(['user_id' => $author->id, 'body' => 'Private post', 'visibility' => 'private']);

        $this->actingAs($viewer)->getJson('/api/feed')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['body' => 'My post'])
            ->assertJsonFragment(['body' => 'Public post'])
            ->assertJsonMissing(['body' => 'Private post']);
    }

    public function test_authenticated_user_can_create_like_and_comment_on_a_post(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author);

        $postResponse = $this->postJson('/api/posts', [
            'body' => 'A story from the API',
            'visibility' => 'friends',
        ])->assertCreated()->assertJsonPath('post.body', 'A story from the API');
        $postId = $postResponse->json('post.id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            DB::table('posts')->where('id', $postId)->value('uuid')
        );

        $this->postJson("/api/posts/{$postId}/like")
            ->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->postJson("/api/posts/{$postId}/comments", ['body' => 'Lovely story'])
            ->assertCreated()->assertJsonPath('comment.body', 'Lovely story');

        $this->getJson("/api/posts/{$postId}/comments")->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_profile_api_does_not_expose_another_users_email_address(): void
    {
        $viewer = User::factory()->create();
        $profile = User::factory()->create(['email' => 'private@example.com']);

        $this->actingAs($viewer)->getJson("/api/users/{$profile->id}")->assertOk()
            ->assertJsonMissingPath('user.email');
        $this->getJson("/api/users/{$viewer->id}")->assertOk()
            ->assertJsonPath('user.email', $viewer->email);
    }

    public function test_friend_requests_receive_uuids(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson("/api/friends/{$recipient->id}")
            ->assertCreated();

        $uuid = DB::table('friendships')->where('id', $response->json('friendship_id'))->value('uuid');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function test_conversation_messages_are_limited_to_participants(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $stranger = User::factory()->create();
        $conversation = Conversation::create(['type' => 'private', 'created_by' => $member->id]);
        $conversation->participants()->attach([$member->id, $other->id]);

        $messageResponse = $this->actingAs($member)->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'Hello there',
        ])->assertCreated()->assertJsonPath('message.body', 'Hello there');
        $this->deleteJson("/api/messages/{$messageResponse->json('message.id')}/me")->assertNoContent();

        $this->actingAs($stranger)->getJson("/api/conversations/{$conversation->id}")
            ->assertForbidden();
    }
}
