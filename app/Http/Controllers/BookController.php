<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    // GET /api/books?author=martin&per_page=10
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->query('per_page', 15), 100); // cap at 100

        $books = Book::query()
            ->byAuthor($request->query('author'))   // scope from Step 4
            ->orderBy('title')
            ->paginate(max($perPage, 1))
            ->withQueryString();                    // keep ?author= in page links

        return BookResource::collection($books);
    }

    // POST /api/books
    public function store(StoreBookRequest $request): BookResource
    {
        $data = $request->validated();                     // only validated fields
        $data['available_copies'] = $data['total_copies']; // new book: all copies on the shelf

        return new BookResource(Book::create($data));      // returns 201 automatically
    }

    // GET /api/books/{book}
    public function show(Book $book): BookResource
    {
        return new BookResource($book);
    }
}