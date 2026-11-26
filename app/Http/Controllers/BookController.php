<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::paginate(15);
        return response()->json([
            'data' => $books,
            'links' => [
                'self' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ],
                'create' => [
                    'href' => url('/api/books'),
                    'method' => 'POST'
                ]
            ]
        ]);
    }

    public function show($id)
    {
        if (!$book = Book::find($id)) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        $user = $book->user()->first();
        $userFiltered['name'] = $user->name;
        $userFiltered['email'] = $user->email;

        return response()->json([
            'data' => $book,
            'user' => $userFiltered,
            'links' => [
                'self' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'GET'
                ],
                'update' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'PUT'
                ],
                'delete' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'DELETE'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'nullable|string',
            'published_year' => 'nullable|integer|min:1000|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $bookData = $request->only(['title', 'author', 'description', 'published_year']);
        $bookData['user_id'] = Auth::id();
        $book = Book::create($bookData);

        return response()->json([
            'data' => $book,
            'links' => [
                'self' => [
                    'href' => url("/api/books/{$book->id}"),
                    'method' => 'GET'
                ],
                'update' => [
                    'href' => url("/api/books/{$book->id}"),
                    'method' => 'PUT'
                ],
                'delete' => [
                    'href' => url("/api/books/{$book->id}"),
                    'method' => 'DELETE'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        if(!$user = Auth::user()){
            return response()->json(['error' => 'Not autorised'], 401);
        }
        if($user->id != $book->user_id){
            return response()->json(['error' => 'Not autorised'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'published_year' => 'nullable|integer|min:1000|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $book->update($request->only(['title', 'author', 'description', 'published_year']));

        return response()->json([
            'data' => $book,
            'links' => [
                'self' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'GET'
                ],
                'delete' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'DELETE'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }

    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        if(!$user = Auth::user()){
            return response()->json(['error' => 'Not autorised'], 401);
        }
        if($user->id != $book->user_id){
            return response()->json(['error' => 'Not autorised'], 401);
        }

        $book->delete();

        return response()->json([
            'message' => 'Book deleted successfully',
            'links' => [
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ],
                'create' => [
                    'href' => url('/api/books'),
                    'method' => 'POST'
                ]
            ]
        ]);
    }

    public function showByUser()
    {
        $user = Auth::user();
        $books = Book::where('user_id', $user->id)->paginate(15);

        return response()->json([
            'data' => $books,
            'links' => [
                'self' => [
                    'href' => url('/api/user/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }
}
