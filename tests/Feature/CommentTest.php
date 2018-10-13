<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Discussion;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

class CommentTest extends TestCase
{
    /**
     * @var string
     */
    private static $endpoint = '/comments';

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function user_can_create_comment()
    {
        factory(Discussion::class)->create(['id' => 1]);
        $this->actingAs($this->user);

        $response = $this->post(
            self::$endpoint,
            [
                'body'             => 'Comment body',
                'commentable_type' => 'discussion',
                'commentable_id'   => 1,
            ]
        );

        $response->assertStatus(201);
        $response->assertJson([
            'status'  => 'success',
            'message' => 'Comment has been saved',
            'comment' => [
                'commentable_id'   => 1,
                'commentable_type' => 'discussion',
                'user_id'          => $this->user->id,
                'body'             => 'Comment body',
            ],
        ]);

        $this->assertDatabaseHas('comments', ['commentable_id' => 1, 'commentable_type' => 'discussion', 'body' => 'Comment body', 'user_id' => $this->user->id]);
    }

    /**
     * @test
     */
    public function guest_can_not_create_comment()
    {
        $this->expectException(AuthenticationException::class, 'Unauthenticated');

        $this->post(
            self::$endpoint,
            [
                'body'             => 'Comment body',
                'commentable_type' => 'discussion',
                'commentable_id'   => 1,
            ]
        );
    }

    /**
     * @test
     */
    public function user_can_not_create_comment_for_non_existing_commentable_resource()
    {
        $this->actingAs($this->user);

        $this->expectException(ValidationException::class);
        $response = $this->post(
            str_replace('1', '999', self::$endpoint),
            [
                'body'             => 'Comment body',
                'commentable_type' => 'discussion',
                'commentable_id'   => 1,
            ]
        );

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function body_attribute_is_required()
    {
        $this->actingAs($this->user);

        $this->expectException(ValidationException::class);

        $this->post(
            self::$endpoint,
            []
        );
    }
}
