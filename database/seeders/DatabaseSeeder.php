<?php

namespace Database\Seeders;

use App\Models\PostComment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run with:  php artisan migrate:fresh --seed
     * Log in with:  demo@example.com / password
     */
    public function run(): void
    {
        // Unguarded so we can set created_at / last_seen_at directly.
        Model::unguarded(function () {
            $demo = $this->createUsers($others);
            $friends = $this->createFriendships($demo, $others);
            $this->createPosts($demo, $friends, $others);
            $this->createChats($demo, $friends);
        });
    }

    /** Creates the demo account plus 14 other users. */
    private function createUsers(&$others): User
    {
        User::factory()->create([
            'name' => 'Admin User',
            'uuid' => str()->random(16),
            'password' => bcrypt('123123'),
            'email' => 'admin@example.com',
            'is_admin' => true,
            'bio' => 'Collector of golden hours and good conversations.',
            'last_seen_at' => now(),
        ]);

        $demo = User::factory()->create([
            'name' => 'Demo User',
            'uuid' => str()->random(16),
            'password' => bcrypt('123123'),
            'email' => 'demo@example.com',
            'bio' => 'Collector of golden hours and good conversations.',
            'last_seen_at' => now(),
        ]);

        $people = [
            ['Amelia Hart', 'Coffee, film photography and long walks.'],
            ['Noah Whitfield', 'Building things on weekends.'],
            ['Sofia Marquez', 'Travel writer. Currently somewhere warm.'],
            ['Liam Okafor', 'Runner, reader, occasional chef.'],
            ['Isabella Rossi', 'Designer with a soft spot for typography.'],
            ['Ethan Brooks', 'Mountains over beaches, always.'],
            ['Mia Chen', 'Plants, podcasts and pour-over.'],
            ['Lucas Meyer', 'Sunday cyclist.'],
            ['Zara Ahmed', 'Baker. Dreamer. Bookworm.'],
            ['Oliver Grant', 'Guitar and bad puns.'],
            ['Chloe Dubois', 'Museum regular.'],
            ['Daniel Park', 'Chasing sunsets with a camera.'],
            ['Hannah Weiss', 'Gardener and tea enthusiast.'],
            ['Samuel Reyes', 'Always up for a road trip.'],
        ];

        $others = collect($people)->map(fn($p) => User::factory()->create([
            'name' => $p[0],
            'bio' => $p[1],
            'password' => bcrypt('123123'),
            // About a third are online right now, the rest were seen hours or days ago.
            'last_seen_at' => rand(1, 3) === 1 ? now()->subMinutes(rand(0, 3)) : now()->subHours(rand(2, 90)),
        ]));

        return $demo;
    }

    /**
     * Demo gets 7 friends, 2 incoming requests, 1 outgoing request.
     * The remaining users stay as suggestions, linked to demo's friends so mutual counts show up.
     */
    private function createFriendships(User $demo, $others)
    {
        $pairs = [];

        $link = function (User $from, User $to, string $status = 'accepted') use (&$pairs) {
            $key = min($from->id, $to->id) . '-' . max($from->id, $to->id);
            if ($from->id === $to->id || isset($pairs[$key])) {
                return;
            }
            $pairs[$key] = true;

            $when = now()->subDays(rand(1, 90));
            Friendship::create([
                'sender_id' => $from->id,
                'recipient_id' => $to->id,
                'status' => $status,
                'accepted_at' => $status === 'accepted' ? $when : null,
                'created_at' => $when,
                'updated_at' => $when,
            ]);
        };

        $friends = $others->slice(0, 7)->values();

        foreach ($friends as $friend) {
            $link($demo, $friend);
        }

        $link($others[7], $demo, 'pending');   // incoming
        $link($others[8], $demo, 'pending');   // incoming
        $link($demo, $others[9], 'pending');   // outgoing

        // Random friendships among everyone else, so mutual friend counts are realistic.
        for ($i = 0; $i < 30; $i++) {
            $link($others->random(), $others->random());
        }

        return $friends;
    }

    private function createPosts(User $demo, $friends, $others): void
    {
        $bodies = [
            "Golden hour hit differently today. Sometimes you just have to stop and look up.",
            "Finally finished the book I've been carrying around for a month. Worth every page.",
            "Trying a new recipe tonight. If you don't hear from me, it went wrong.",
            "Morning run done. The city is so quiet before 7am.",
            "Does anyone have a good podcast recommendation for a long drive?",
            "Small wins today: answered every email, watered the plants, and took a proper lunch break.",
            "Found a tiny café with the best flat white I've ever had. Not telling where yet.",
            "Weekend plan: absolutely nothing, and I couldn't be happier about it.",
            "Learning to play guitar at 30. My neighbours are very patient people.",
            "Rainy days are for blankets, tea and old movies.",
            "Just booked tickets for next month. Counting down already!",
            "Reminder to drink water and stretch. That's the whole post.",
            "Sunday market haul: fresh bread, strawberries and far too many flowers.",
            "Three years ago today I moved here. Best decision I ever made.",
            "Started a new sketchbook today. First page is always the hardest.",
            "Hot take: breakfast for dinner is always a good idea.",
            "Spent the afternoon at the museum and lost track of time completely.",
            "Who else is excited for the long weekend? Any plans?",
            "Cooked for friends tonight. Nothing beats a full table and good conversation.",
            "Took the scenic route home and it was absolutely worth it.",
        ];

        $circle = $friends->concat([$demo]);
        $everyone = $others->concat([$demo]);

        for ($i = 0; $i < 45; $i++) {
            // Most posts come from demo's circle so the feed looks full; some come from strangers.
            $author = rand(1, 10) <= 7 ? $circle->random() : $everyone->random();
            $roll = rand(1, 100);

            Post::create([
                'user_id' => $author->id,
                'body' => $bodies[array_rand($bodies)],
                'visibility' => $roll <= 30 ? 'public' : ($roll <= 95 ? 'friends' : 'private'),
                'created_at' => $created = now()->subMinutes(rand(5, 20160)),
                'updated_at' => $created,
            ]);
        }

        $this->addLikesAndComments($everyone);
    }

    private function addLikesAndComments($users): void
    {
        $comments = [
            'Love this!',
            'Totally agree.',
            'This made my day.',
            'Where is this?',
            'Needed to hear that today.',
            'Haha, same here.',
            'Take me with you next time!',
            'Beautiful.',
            'Okay, now I want to try it too.',
            'Let us catch up soon.',
        ];
        $replies = ['Thank you!', 'Anytime.', 'Definitely, let us plan something.', 'Ha, glad you liked it.'];

        foreach (Post::all() as $post) {
            // Likes: 0 to 8 distinct users.
            foreach ($users->random(rand(0, 8)) as $liker) {
                PostReaction::create(['post_id' => $post->id, 'user_id' => $liker->id, 'created_at' => $post->created_at->copy()->addMinutes(rand(1, 600))]);
            }

            // Comments on roughly 60% of posts, some with a reply from the post author.
            if (rand(1, 10) > 6) {
                continue;
            }
            for ($c = 0; $c < rand(1, 4); $c++) {
                $comment = PostComment::create([
                    'post_id' => $post->id,
                    'user_id' => $users->random()->id,
                    'body' => $comments[array_rand($comments)],
                    'created_at' => $at = $post->created_at->copy()->addMinutes(rand(2, 900)),
                    'updated_at' => $at,
                ]);

                if (rand(1, 10) <= 3) {
                    PostComment::create([
                        'post_id' => $post->id,
                        'user_id' => $post->user_id,
                        'parent_id' => $comment->id,
                        'body' => $replies[array_rand($replies)],
                        'created_at' => $at->copy()->addMinutes(rand(1, 120)),
                        'updated_at' => $at->copy()->addMinutes(rand(1, 120)),
                    ]);
                }
            }
        }
    }

    /** One private chat and one group chat, both with unread messages for the demo user. */
    private function createChats(User $demo, $friends): void
    {
        $first = $friends[0];

        $private = Conversation::create(['type' => 'private', 'created_by' => $demo->id]);
        $private->participants()->attach([$demo->id, $first->id]);

        $this->addMessages($private, [
            [$demo, 'Hey! Are we still on for coffee this weekend?'],
            [$first, 'Absolutely. Saturday morning works for me.'],
            [$demo, 'Perfect. Same café as last time?'],
            [$first, 'Yes, and I will bring the book I mentioned.'],
            [$first, 'Also, did you see the photos I posted?'],
        ]);

        $group = Conversation::create(['type' => 'group', 'title' => 'The Golden Circle', 'created_by' => $demo->id]);
        $group->participants()->attach($demo->id, ['role' => 'admin']);
        $group->participants()->attach([$friends[1]->id, $friends[2]->id, $friends[3]->id]);

        $this->addMessages($group, [
            [$demo, 'Welcome to the group, everyone!'],
            [$friends[1], 'Thanks for adding me.'],
            [$friends[2], 'Who is up for a weekend trip next month?'],
            [$friends[3], 'Count me in.'],
        ]);
    }

    private function addMessages(Conversation $conversation, array $lines): void
    {
        $time = now()->subHours(count($lines) * 2);

        foreach ($lines as [$sender, $body]) {
            $time = $time->copy()->addMinutes(rand(5, 90));
            $conversation->messages()->create([
                'user_id' => $sender->id,
                'body' => $body,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        $conversation->update(['last_message_at' => $time]);
    }
}
