drop database if exists Online_Cake_Project;
CREATE database Online_Cake_Project;
USE Online_Cake_Project;


CREATE TABLE Products (
    ID INT PRIMARY KEY
    AUTO_INCREMENT,
    NameEN VARCHAR(255),
    Price DECIMAL(10,2),
    Image VARCHAR(255),
    NameFR VARCHAR(255)
);

INSERT INTO products (ID, NameEN, Price, Image, NameFR) VALUES
(1, 'Luxurious Chocolate Fudge Cake',70.01, 'luxuriousChocola.webp', 'Gâteau fondant au chocolat luxueux'),
(2, 'Delicate Vanilla Sponge Cake', 60.03, 'vanilla sponge cake.webp', 'Gâteau éponge à la vanille délicat'),
(3, 'Rich Red Velvet Cake', 50.01, 'rich Red Velvet Cake.webp', 'Gâteau velours rouge riche'),
(4, 'Carrot cake', 60.04, 'Carrot Cake.webp', 'Gâteau aux carottes'),
(5, 'Lemon Drizzle Cake', 65.08, 'Lemon Drizzle Cake.webp', 'Gâteau au citron glaçage'),
(6, 'Black Forest Cake', 55.05, 'Black Forest Cake.webp', 'Forêt-Noire'),
(7, 'Matcha Green Tea Cake', 70.09, 'Matcha Green Tea Cake.webp', 'Gâteau au thé vert Matcha');





create table Clients (
    ClientId int primary key auto_increment,
   UserName varchar(255),
   Password varchar(255),
    Email varchar(255),
    Phone int,
    Address varchar(255),
    defaultRole varchar(255)
);




/*CREATE TABLE Orders (
    OrderID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(255),
    OrderDate DATE,
    OrderTime TIME,
    ProductNames TEXT,
    Prices DECIMAL(10,2),
    TotalPrice DECIMAL(10,2)
);*/

CREATE TABLE Orders (
    OrderID INT PRIMARY KEY AUTO_INCREMENT,
    ClientID INT,
    OrderDate DATETIME,
    TotalPrice DECIMAL(10,2),
    FOREIGN KEY (ClientID) REFERENCES Clients(ClientId)
);

ALTER TABLE Orders ADD Status VARCHAR(20) DEFAULT 'Pending';




CREATE TABLE OrderItems (
    OrderItemID INT PRIMARY KEY AUTO_INCREMENT,
    OrderID INT,
    ProductID INT,
    Quantity INT DEFAULT 1,
    Price DECIMAL(10,2),
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID),
    FOREIGN KEY (ProductID) REFERENCES Products(ID)
);



CREATE TABLE Translations (
    ID VARCHAR(100) PRIMARY KEY,
    English TEXT,
    French TEXT
);

INSERT INTO Translations (ID, English, French) VALUES
('Home', 'Home', 'Accueil'),
('Total', 'Total', 'Total'),
('About', 'About', 'A propos'),
('Contact', 'Contact', 'Contact'),
('Cart', 'Cart', 'Panier'),
('Products', 'Products', 'Produits'),
('Status', 'Status', 'Statut'),
('AddProduct', 'Add Product', 'Ajouter un produit'),
('Login', 'Login', 'Connexion'),
('Register', 'Register', 'Inscription'),
('Logout', 'logout', 'déconnexion'),
('Welcome', 'Welcome', 'Bienvenue'),
('Unknown', 'Unknown user', 'Utilisateur inconnu'),
('French', 'French', 'Français'),
('English', 'English', 'Anglais'),
('Change Password', 'Change Password', 'Changer le mot de passe'),
('Orders', 'Orders', 'Commandes'),
('All Orders', 'All Orders', 'Toutes les commandes'),
('Order', 'Order', 'Commande'),
('Order Date', 'Order Date', 'Date de commande'),
('Product Name', 'Product Name', 'Nom du produit'),
('Price', 'Price', 'Prix'),
('Total Price', 'Total Price', 'Prix total'),
('Order Time', 'Order time', 'Heure de commande'),
('Username', 'Username', "Nom d'utilisateur"),
('Your Shopping Cart', 'Your Shopping Cart', "Votre panier d'achat"),
('Your cart is empty', 'Your cart is empty!', 'Votre panier est vide!'),
('Your Order History', 'Your Order History', 'Votre historique de commandes'),
('Remove', 'Remove', 'Retirer'),
('Finalize Order', 'Finalize Order', 'Finaliser la commande'),
('Clear Cart', 'Clear Cart', 'Vider le panier'),
('No orders found', 'No orders found', 'Aucune commande trouvée'),
('Login to add products to your cart', 'Login to add products to your cart', 'Connectez-vous pour ajouter des produits à votre panier'),
('Add to cart', 'Add to cart', 'Ajouter au panier'),
('Submit', 'Submit', 'Soumettre'),
('ABOUT US', 'ABOUT US', 'À PROPOS DE NOUS'),
('Log in to your account', 'Log in to your account', 'Connectez-vous à votre compte'),
('WELCOME TO THE WORLD OF CAKES', 'WELCOME TO THE WORLD OF CAKES', 'BIENVENUE DANS LE MONDE DES GÂTEAUX'),
('Perfect for every celebration.', 'Perfect for every celebration.', 'Parfait pour chaque célébration.'),
('Explore Our Cakes', 'Explore Our Cakes', 'Découvrez Nos Gâteaux'),
('aboutUs', 
 "We believe every celebration deserves a masterpiece. Our cakes are crafted with the finest ingredients and a touch of magic to make your moments unforgettable. Whether you're celebrating birthdays anniversaries or any special occasion our cakes are here to add sweetness to your moments.", 
 "Nous croyons que chaque célébration mérite un chef-d'œuvre. Nos gâteaux sont confectionnés avec les meilleurs ingrédients et une touche de magie pour rendre vos moments inoubliables. Que vous célébriez des anniversaires, des mariages ou toute autre occasion spéciale, nos gâteaux ajoutent de la douceur à vos moments."),
('Pending', 'Pending', 'En attente'),
('Delivered', 'Delivered', 'Livré');
