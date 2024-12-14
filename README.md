# Library System API

The PHP-based RESTful API enables CRUD (Create, Read, Update, Delete) operations for managing books and authors within a library system. Built using the Slim Framework, it adheres to standard RESTful principles, ensuring compatibility with various client applications. The API incorporates JWT-based authentication to secure access to its endpoints, allowing only authorized users to manage the library's catalog of books and authors.

# API Features
##### Secure Token-Based Authentication: 
Each request is authenticated with a JSON Web Token (JWT), ensuring only authorized users can perform operations. Tokens are validated with each request and refreshed as necessary for session continuity.

##### User Registration and Authentication: 
Allows users to register and log in securely. Each request is authenticated with a JSON Web Token (JWT), which is validated with each request and refreshed as needed to maintain session continuity.

##### CRUD Operations for Authors: 
- ###### Create Author: 
    Adds a new author to the database.
- ###### Read Authors:
    Retrieves a list of all authors.
- ###### Update Author:
    Allows modification of an author’s name.
- ###### Delete Author: 
    Removes an author, first deleting any associated books to maintain referential integrity.
 

##### CRUD Operations for Books: 
- ###### Create Book:
    Adds a new book to the database, checking for duplicate titles by the same author.
- ###### Read Books:
    Retrieves the list of all books in the database.
- ###### Update Book: 
    Modifies the title of an existing book, with validations to prevent duplicate entries by the same author.
- ###### Delete Book: 
    Removes a book from the database, first deleting any references to maintain referential integrity.

###### Retrieve Authors and Their Books: 
This endpoint lists all authors in the database along with the titles of their books, providing a structured response suitable for both displaying and filtering books by author.


# Endpoints

## 1. User Registration
###### Description: Registers a new user by storing their username and password securely.
- Endpoint: /user/register
- Method: POST
- Request Payload:
```sh
{
    "username": "<string>",
    "password": "<string>"
}
```
Response: 
- Success (200):
```sh
{
    "status": "success",
    "data": null
}
```
- Failure:
```sh
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```

## 2. User Authentication
###### Description: Verifies the provided credentials and issues a JWT token if successful.
- Endpoint: /user/authenticate
- Method: POST
- Request Payload:
``` sh
{
    "username": "<string>",
    "password": "<string>"
}
Responses: 
```
- Success (200):
``` sh
{
    "status": "success",
    "token": "<JWT token>"
}
```
- Failure:
``` sh
{
    "status": "fail",
    "data": {
        "title": "Invalid credentials"
    }
}
```
## 3.  Get User Information
###### Description: Retrieves the details of the currently authenticated user.
- Endpoint: /user/read
- Method: GET
Response:

- Success (200):
```sh
{
    "status": "success",
    "data": {
        "userid": "<integer>",
        "username": "<string>"
    }
}
```
- Failure:
```sh
{
    "status": "fail",
    "data": {
        "title": "User not found"
    }
}
```

- Unauthorized:
```sh
{
    "status": "fail",
    "data": {
        "title": "Unauthorized"
    }
}
```
## 4. Update User
###### Description: Updates the user's username and/or password. A new JWT token is issued after the update.
- Endpoint: /user/update
- Method: PUT
- Request Payload:
```sh
{
    "username": "<string>",
    "password": "<string>"
}
```
 Response:
- Success (200):
```sh
{
    "status": "success",
    "token": "<new JWT token>"
}
```
- Failure:
```sh
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```
- Unauthorized Response:
```sh
{
    "status": "fail",
    "data": {
        "title": "Unauthorized"
    }
}
```
## 5. Delete User
###### Description: Deletes the user's account from the system. A token is issued for the delete operation.
- Endpoint: /user/delete
- Method: DELETE
Response:
- Success (200):
```sh
{
    "status": "success",
    "token": "<new JWT token>"
}
```
- Failure:
```sh
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```
- Unauthorized Response:
```sh
{
    "status": "fail",
    "data": {
        "title": "Unauthorized"
    }
}
```
# Book-Author Endpoints
## 6. Create Book-Author Relationship
###### Description: Establishes a relationship between a book and an author in the system.
- Endpoint: /book_authors/register
- Method: POST
Request Payload:
```sh
{
    "bookid": "<integer>",
    "authorid": "<integer>"
}
```
Response:
- Success (200):
```sh
{
    "status": "success",
    "data": null
}
```
- Failure:
```sh 
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```
## 7. Get All Book-Authors
###### Description: Retrieves all the relationships between books and authors.
- Endpoint: /book_authors/show
- Method: GET
Response:
- Success (200):
```sh
{
    "status": "success",
    "data": [
        {
            "collectionid": 1,
            "bookid": 101,
            "authorid": 202
        },
        {
            "collectionid": 2,
            "bookid": 102,
            "authorid": 203
        }
    ]
}

```
- Failure:
```sh 
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```

## 8. Update Book-Author Relationship
###### Description: Updates the relationship between a book and an author, identified by its collection ID.
- Endpoint: /book_authors/update/{collectionid}
- Method: PUT
Request Payload:
```sh
{
    "bookid": 103,
    "authorid": 204
}

```
Response:
- Success (200):
```sh
{
    "status": "success",
    "data": null
}

```
- Failure:
```sh 
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```
- Unauthorized Response:
```sh 
{
    "status": "fail",
    "data": {
        "title": "Unauthorized"
    }
}
```
## 9. Delete Book-Author Relationship
###### Description: Deletes a specific book-author relationship identified by its collection ID.
- Endpoint: /book_authors/delete/{collectionid}
- Method: DELETE
Response:
- Success (200):
```sh
{
    "status": "success",
    "data": null
}

```
- Failure:
```sh 
{
    "status": "fail",
    "data": {
        "title": "<error message>"
    }
}
```
- Unauthorized Response:
```sh 
{
    "status": "fail",
    "data": {
        "title": "Unauthorized"
    }
}
```
# Book Endpoints
## 10. Get All Books
###### Description: Retrieves a list of all books in the library.
- Endpoint: /books/read
- Method: POST
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": [
    {
      "bookid": 1,
      "title": "Book Title",
      "authorid": 1
    },
    ...
  ],
  "token": "JWT_Token"
}
```
- Failure:
```sh 
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Database connection failed'"
  }
}
```
## 11. Create a New Book
###### Description: Registers a new book in the library.
- Endpoint: /books/register
- Method: POST
Request Payload:
```sh
{
  "title": "Book Title",
  "authorid": 1
}
```
Response:
- Success (200):
```sh
{
 {
  "status": "success",
  "data": null
}

}
```
- Failure:
```sh
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Missing required fields'"
  }
}
```
## 12. Update a Book
###### Description: Updates an existing book's information.
- Endpoint: /books/update/{bookid}
- Method: PUT
Request Payload:
```sh
{
  "title": "Updated Book Title",
  "authorid": 2
}
```
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": null
}
```
- Failure:
```sh
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Book not found'"
  }
}
```
## 13. Delete a Book
###### Description: Deletes a book from the library.
- Endpoint: /books/delete/{bookid}
- Method: DELETE
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": null
}

- Failure:
```sh
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Book not found'"
  }
}
```
# Book Endpoints
## 14. Get All Books
###### Description: Retrieves a list of all authors.
- Endpoint: /authors/read
- Method: GET
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": [
    {
      "authorid": 1,
      "name": "Author Name"
    },
    ...
  ],
  "token": "JWT_Token"
}

```
- Failure:
```sh 
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Database connection failed'"
  }
}
```
## 15. Create a New Author
###### Description: Registers a new author in the library.
- Endpoint: /authors/register
- Method: POST
Request Payload:
```sh
{
  "name": "Author Name"
}
```
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": null
}
```
- Failure:
```sh 
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Author name already exists'"
  }
}
```
## 16. Update an Author
###### Description: Updates an existing author's information
- Endpoint: /authors/update/{id}
- Method: PUT
Request Payload:
```sh
{
  "name": "Updated Author Name"
}
```
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": null
}
```
- Failure:
```sh 
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Author not found'"
  }
}
```
## 17. Delete an Author
###### Description: Deletes an author from the library.
- Endpoint: /authors/delete/{id}
- Method: DELETE
Response:
- Success (200):
```sh
{
  "status": "success",
  "data": null
}
```
- Failure:
```sh 
{
  "status": "fail",
  "data": {
    "title": "Error message, e.g., 'Author not found'"
  }
}
```

##### These API implements security and reliability measures, including:
- Tokens are refreshed with each request.
- Detailed error messages are returned in case of database errors or validation failures.
- Careful handling of related entries (e.g., books and authors) ensures that deletions maintain database integrity by clearing linked references before proceeding.























