<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;   // fresh, empty database for every test

    public function test_it_creates_a_book_with_all_copies_available(): void
    {
        $response = $this->postJson('/api/books', [
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'total_copies' => 3,
        ]);

        $response->assertCreated()                               // 201
            ->assertJsonPath('data.title', 'Clean Code')
            ->assertJsonPath('data.available_copies', 3);

        $this->assertDatabaseHas('books', ['isbn' => '9780132350884', 'available_copies' => 3]);
    }

    public function test_it_rejects_invalid_book_data(): void
    {
        $this->postJson('/api/books', ['title' => '', 'isbn' => 'abc', 'total_copies' => 0])
            ->assertUnprocessable()                              // 422
            ->assertJsonValidationErrors(['title', 'author', 'isbn', 'total_copies']);
    }

    public function test_isbn_must_be_unique(): void
    {
        Book::factory()->create(['isbn' => '9780132350884']);

        $this->postJson('/api/books', [
            'title' => 'Copy',
            'author' => 'Someone',
            'isbn' => '9780132350884',
            'total_copies' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('isbn');
    }

    public function test_it_lists_books_with_pagination_and_author_filter(): void
    {
        Book::factory()->create(['author' => 'Martin Fowler']);
        Book::factory()->create(['author' => 'Robert C. Martin']);
        Book::factory()->create(['author' => 'Kent Beck']);

        $this->getJson('/api/books?author=martin')
            ->assertOk()                                          // 200
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'total']]);
    }

    public function test_unknown_book_returns_404(): void
    {
        $this->getJson('/api/books/999')->assertNotFound();
    }
}