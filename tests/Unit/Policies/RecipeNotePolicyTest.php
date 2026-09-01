<?php

namespace Tests\Unit\Policies;

use App\Models\RecipeNote;
use App\Models\User;
use App\Policies\RecipeNotePolicy;
use PHPUnit\Framework\TestCase;

final class RecipeNotePolicyTest extends TestCase
{
    public function test_only_author_can_view_update_and_delete_note(): void
    {
        $policy = new RecipeNotePolicy;
        $author = new User;
        $author->id = 10;
        $otherUser = new User;
        $otherUser->id = 20;
        $note = new RecipeNote;
        $note->author_id = 10;

        $this->assertTrue($policy->view($author, $note));
        $this->assertTrue($policy->update($author, $note));
        $this->assertTrue($policy->delete($author, $note));
        $this->assertFalse($policy->view($otherUser, $note));
        $this->assertFalse($policy->update($otherUser, $note));
        $this->assertFalse($policy->delete($otherUser, $note));
    }
}
