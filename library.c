#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// -------- LINKED LIST FOR BOOKS --------
struct Book {
    char name[50];
    struct Book *next;
};

struct Book *head = NULL;

// -------- QUEUE FOR ISSUE --------
struct Queue {
    char books[100][50];
    int front, rear;
} q = {.front = -1, .rear = -1};

// -------- STACK FOR RETURN --------
struct Stack {
    char books[100][50];
    int top;
} s = {.top = -1};

// -------- ADD BOOK (Linked List) --------
void addBook(char name[]) {
    struct Book *newBook = (struct Book*)malloc(sizeof(struct Book));
    strcpy(newBook->name, name);
    newBook->next = head;
    head = newBook;
}

// -------- ISSUE BOOK (Queue) --------
void issueBook(char name[]) {
    if (q.rear == 99) {
        printf("Queue Full");
        return;
    }

    if (q.front == -1) q.front = 0;

    q.rear++;
    strcpy(q.books[q.rear], name);

    FILE *fp = fopen("records.txt", "a");
    fprintf(fp, "ISSUED: %s\n", name);
    fclose(fp);

    printf("Book Issued (Queue)");
}

// -------- RETURN BOOK (Stack) --------
void returnBook(char name[]) {
    if (s.top == 99) {
        printf("Stack Overflow");
        return;
    }

    s.top++;
    strcpy(s.books[s.top], name);

    FILE *fp = fopen("records.txt", "a");
    fprintf(fp, "RETURNED: %s\n", name);
    fclose(fp);

    printf("Book Returned (Stack)");
}

// -------- SEARCH BOOK (Linked List) --------
void searchBook(char name[]) {
    struct Book *temp = head;

    while (temp != NULL) {
        if (strcmp(temp->name, name) == 0) {
            printf("Book Found");
            return;
        }
        temp = temp->next;
    }

    printf("Book Not Found");
}

// -------- MAIN FUNCTION --------
int main(int argc, char *argv[]) {

    if (argc < 3) {
        printf("Invalid Input");
        return 1;
    }

    char *action = argv[1];
    char *book = argv[2];

    // Add some default books (for demo)
    addBook("C Programming");
    addBook("Data Structures");
    addBook("Operating Systems");

    if (strcmp(action, "issue") == 0) {
        issueBook(book);
    }
    else if (strcmp(action, "return") == 0) {
        returnBook(book);
    }
    else if (strcmp(action, "search") == 0) {
        searchBook(book);
    }
    else {
        printf("Invalid Action");
    }

    return 0;
}