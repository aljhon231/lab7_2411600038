# Laboratory Exercise 5 - Guide Answers

These are short explanations of how the inventory application is designed. They describe the code and features in this project; they do not claim that a person performed steps that have not been tested.

## Question 1: MVC architecture

MVC means Model, View, and Controller. The Model represents application data and database relationships. In this application, `Product` represents inventory products and `InventoryTransaction` represents stock movements. The View is the page shown in the browser, such as the product list Blade template. The Controller receives a request, validates it, calls the models, and chooses the response.

### Server-side MVC and client-side JavaScript

In server-side MVC, the browser sends a request to Laravel. Laravel checks the request, reads or updates MySQL, and returns a rendered page. Client-side JavaScript runs in the browser and can respond to clicks or update a page without first requesting a new page. JavaScript is useful for user-interface behavior, but it should not be the only way inventory data is stored or protected.

### Advantages of Laravel

Laravel provides organized routing, controllers, Blade templates, Eloquent ORM, migrations, validation, authentication, and CSRF protection. These features help keep related tasks in their proper places and reduce the need to write common security and database behavior from scratch.

### HTTP request flow

For example, when a signed-in user saves a stock movement, the browser sends a POST request with a CSRF token. Laravel matches the named route and checks authentication. The controller validates the values, opens a database transaction, locks the product row, updates its quantity, and saves an inventory transaction. Laravel then redirects to the transaction list and displays a success message.

## Question 2: Eloquent ORM and database migrations

Eloquent ORM lets PHP code work with database rows as model objects. For example, `Product::query()->where('quantity', 0)->get()` finds products with no stock. `$product->inventoryTransactions()->create([...])` records a movement related to that product.

A migration describes a database structure change in code. The products migration creates columns such as SKU and quantity. The inventory-transactions migration creates the movement table and its foreign keys. Migrations can be applied consistently on another computer with Artisan.

MySQL stores data on the server and is the shared source used by Laravel. Browser `localStorage` stores values only in one browser profile and can be changed by that user. It is not a suitable primary database for shared stock records.

Server-side security matters because users can change browser requests. Laravel validates submitted values, checks authentication, uses CSRF tokens, hashes passwords, and uses database constraints. Database credentials belong in the local `.env` file and must not be published.

## Question 3: Separation of Concerns and input validation

The Lab 4 student portal uses HTML and CSS for its pages and JavaScript with `localStorage` for browser-side sample data. Lab 5 uses Laravel MVC: routes and controllers process requests, Eloquent models access MySQL, and Blade templates display the result. The layout and green color palette from the supplied Lab 4 CSS are reused, while the inventory data is handled by the Laravel backend.

Client-side-only validation can be bypassed by sending a different request directly. Browser storage is local to one browser and does not provide a reliable shared inventory record. Server-side validation checks values before Laravel writes them to the database. It helps reject missing names, duplicate SKUs, negative quantities, and invalid prices.

Separating concerns makes the project easier to understand. The model describes data, the controller contains request and business logic, and the Blade view handles presentation.

## Question 4: Color scheme integration

The supplied Lab 4 stylesheet uses a dark green primary color (`#0d5c3e`), a lighter green (`#1a936f`), a pale green background (`#f0f7f4`), dark green text (`#1a2e24`), and a yellow accent (`#f3a712`). It uses Arial, Helvetica, or sans-serif fonts. These values are kept as CSS variables in `public/css/custom.css`.

The Laravel pages connect that visual design to the backend: a Blade template displays the database records returned by a controller, and the custom stylesheet gives those pages a consistent look. Reusing the same colors, font, and navigation style helps the new application feel related to the earlier exercise without changing the existing Lab 4 files.

## Conclusion

This project demonstrates how Laravel MVC, Blade, Eloquent, migrations, validation, authentication, and MySQL can work together in an inventory application. It also shows how a previous exercise's visual design can be reused while replacing browser-only sample data with server-side database records.
