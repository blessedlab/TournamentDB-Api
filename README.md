# 2v2 Tournament database API

A simple and lightweight PHP REST API for managing tournament teams and their participants. It handles data in JSON format and uses PDO for secure database interactions.

## Features
- **Create a team and its players** in a single request.
- **Delete a team and all its players** by providing just one participant's ID.
- Secure SQL execution using PDO Prepared Statements.
- CORS enabled for frontend integration.

---

## Setup & Installation

1. Clone the repository to your local server (e.g., XAMPP, MAMP).
2. Create a MySQL database named `tournament`.
3. Create a user `api` with the password `Qwerty4!api` (or change the credentials in the PHP file).
4. Run the following SQL queries to create the required tables:

```sql
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    team_id INT,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);
```

---

## API Documentation

**Endpoint:** `http://localhost/TournamentDB-Api.php` (Adjust the URL based on your server setup).

### 1. Register a Team and Participants
Creates a new team and registers multiple players assigned to that team. The API performs the following checks before creating a team:
- Team name must be unique (not already taken)
- Each participant's email and nickname must be unique (not already registered)

* **Method:** `POST`
* **Headers:** `Content-Type: application/json`

**Request Body (JSON):**
```json
{
    "team_name": "Cyber Ninjas",
    "participants": [
        {
            "nickname": "Shadow",
            "email": "shadow@tm1.edu.pl"
        },
        {
            "nickname": "Ghost",
            "email": "ghost@tm1.edu.pl"
        }
    ]
}
```

**Success Response (200 OK):**
```json
{
    "error": 0,
    "message": "Participants and team added successfully"
}
```

**Error Responses:**

- Team name already exists:
    ```json
    { "error": 1, "message": "Team name 'Cyber Ninjas' is already taken!" }
    ```
- Email already exists:
    ```json
    { "error": 1, "message": "shadow@tm1.edu.pl" }
    ```
- Nickname already exists:
    ```json
    { "error": 1, "message": "Nickname already exists: Shadow" }
    ```

---

### 2. Delete a Team and its Participants
Deletes an entire team and all its associated players using the `id` of **any single participant** belonging to that team.

* **Method:** `DELETE`
* **Headers:** `Content-Type: application/json`

**Request Body (JSON):**
```json
{
    "id": 5
}
```
*(Where `5` is the ID of a participant in the `participants` table).*

**Success Response (200 OK):**
```json
{
    "message": "Participants and team deleted successfully"
}
```

**Error Response (Participant not found):**
```json
{
    "message": "Participant not found"
}
```

---

### 3. Unsupported Methods
If you try to access the API using an unsupported HTTP method (like `GET` or `PUT`), it will return an error.

**Response:**
```json
{
    "message": "Method not supported"
}
```

---

## Frontend Integration Example (JavaScript)

You can easily interact with this API using the native JS `fetch` API. Example (see also index.js for form validation):

```javascript
const requestData = {
    team_name: "NaVi",
    participants: [
        { nickname: "S1mple", email: "s1mple@tm1.edu.pl" }
    ]
};

fetch('http://localhost/TournamentDB-Api.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(requestData)
})
.then(response => response.json())
.then(data => {
    if(data.error) {
        console.error(data.message);
    } else {
        console.log(data.message);
    }
});
```
