-- ============================================================
--  Atheneum — Seed Data
--  Run AFTER schema.sql
--
--  Admin login:  username=admin  password=Admin@1234
--  Demo login:   username=demo   password=Admin@1234
-- ============================================================
USE Atheneum;

-- ── Users ─────────────────────────────────────────────────────
INSERT INTO Users (username, email, fname, lname, password, role, profile_pic) VALUES
('admin', 'admin@atheneum.sg', 'Admin', 'Atheneum', '$2y$10$OHoNGaQ34IQbfgrKc/XRoe8KfkeHJzaxImpIZeqvuzisYKTBxq4DC', 'admin', 'assets/images/default-avatar.jpg'),
('demo',  'demo@atheneum.sg',  'Demo',  'User',     '$2y$10$OHoNGaQ34IQbfgrKc/XRoe8KfkeHJzaxImpIZeqvuzisYKTBxq4DC', 'customer', 'assets/images/default-avatar.jpg');

-- ── Products — 30 books (10 per genre) ───────────────────────

-- Fiction & Literature
INSERT INTO Products (title, author, genre, price, quantity, description, cover_image) VALUES
('The Midnight Library','Matt Haig','Fiction & Literature',18.90,25,'Somewhere out beyond the edge of the universe there is a library that contains an infinite number of books, each one the story of another reality. When Nora Seed finds herself in the Midnight Library, she has a chance to make things right.','assets/images/Fiction & Literature/The Midnight Library.jpg'),
('The Kite Runner','Khaled Hosseini','Fiction & Literature',17.90,20,'Set in Afghanistan from the 1970s through to 2001, this sweeping story of betrayal, guilt and redemption follows Amir and his childhood friend Hassan. A devastating and ultimately redemptive tale of fathers and sons.','assets/images/Fiction & Literature/The Kite Runner.jpg'),
('The Great Gatsby','F. Scott Fitzgerald','Fiction & Literature',14.90,3,'Set in the Jazz Age on Long Island, the novel depicts narrator Nick Carraway\'s interactions with mysterious millionaire Jay Gatsby and his obsession to reunite with his former lover. A searing critique of the American Dream.','assets/images/Fiction & Literature/The Great Gatsby.jpg'),
('To Kill a Mockingbird','Harper Lee','Fiction & Literature',16.90,18,'Pulitzer Prize-winning masterwork of honour and injustice in the deep American South. Seen through the innocent eyes of Scout Finch, the novel follows her father Atticus as he defends a Black man falsely accused of a crime.','assets/images/Fiction & Literature/To Kill a Mockingbird.jpg'),
('1984','George Orwell','Fiction & Literature',15.90,0,'In a bleak and totalitarian future, Winston Smith works for the Party rewriting historical records. When he falls in love with Julia he risks everything. A timeless warning about the dangers of authoritarianism.','assets/images/Fiction & Literature/1984.jpg'),
('Norwegian Wood','Haruki Murakami','Fiction & Literature',19.90,12,'Toru Watanabe looks back on his days as a student in 1960s Tokyo and his love for two very different women. A poignant, beautiful, and honest portrayal of loss and coming of age.','assets/images/Fiction & Literature/Norwegian Wood.jpg'),
('Klara and the Sun','Kazuo Ishiguro','Fiction & Literature',22.90,15,'From Nobel laureate Kazuo Ishiguro, this is the story of Klara, an Artificial Friend with outstanding observational qualities. A luminous story about what it means to love.','assets/images/Fiction & Literature/Klara and the Sun.jpg'),
('The Alchemist','Paulo Coelho','Fiction & Literature',16.90,30,'The magical story of Santiago, an Andalusian shepherd boy who yearns to travel in search of a worldly treasure. A global phenomenon with over 65 million copies sold.','assets/images/Fiction & Literature/The Alchemist.jpg'),
('Pachinko','Min Jin Lee','Fiction & Literature',21.90,10,'Following one Korean family through four generations, Pachinko is a powerful story of love, sacrifice, ambition, and loyalty. An extraordinary novel about family and identity.','assets/images/Fiction & Literature/Pachinko.jpg'),
('The God of Small Things','Arundhati Roy','Fiction & Literature',18.90,8,'Set in Kerala, India, this masterpiece tells the story of twins whose lives are profoundly altered by their family history. Winner of the Booker Prize.','assets/images/Fiction & Literature/The God of Small Things.jpg');

-- Non-Fiction & Self Help
INSERT INTO Products (title, author, genre, price, quantity, description, cover_image) VALUES
('Atomic Habits','James Clear','Non-Fiction & Self Help',24.90,35,'No matter your goals, Atomic Habits offers a proven framework for improving every day. Practical strategies that will teach you exactly how to form good habits and break bad ones.','assets/images/Non-Fiction & Self Help/Atomic Habits.jpg'),
('The Psychology of Money','Morgan Housel','Non-Fiction & Self Help',22.90,28,'Timeless lessons on wealth, greed, and happiness. Doing well with money is about how you behave, not what you know. 19 short stories exploring the strange ways people think about money.','assets/images/Non-Fiction & Self Help/The Psychology of Money.jpg'),
('Sapiens','Yuval Noah Harari','Non-Fiction & Self Help',26.90,22,'How did our species succeed in the battle for dominance? A bold, wide-ranging and groundbreaking history of humankind from the Stone Age to the present.','assets/images/Non-Fiction & Self Help/Sapiens.jpg'),
('Thinking, Fast and Slow','Daniel Kahneman','Non-Fiction & Self Help',28.90,16,'Nobel Prize-winner Daniel Kahneman reveals the two systems that drive the way we think — fast, intuitive thinking and slow, deliberative thinking.','assets/images/Non-Fiction & Self Help/Thinking, Fast and Slow.jpg'),
('Deep Work','Cal Newport','Non-Fiction & Self Help',21.90,0,'Deep work is the ability to focus without distraction on a cognitively demanding task. Cal Newport argues it is becoming increasingly rare and increasingly valuable.','assets/images/Non-Fiction & Self Help/Deep Work.jpg'),
('Educated','Tara Westover','Non-Fiction & Self Help',23.90,14,'An unforgettable memoir about a young girl who leaves her survivalist family and goes on to earn a PhD from Cambridge University.','assets/images/Non-Fiction & Self Help/Educated.jpg'),
('Man\'s Search for Meaning','Viktor E. Frankl','Non-Fiction & Self Help',17.90,20,'Viktor Frankl\'s riveting account of his time in the Nazi concentration camps and his groundbreaking theories on the importance of meaning to human survival.','assets/images/Non-Fiction & Self Help/Man\'s Search for Meaning.jpg'),
('Ikigai','Héctor García & Francesc Miralles','Non-Fiction & Self Help',19.90,4,'The people of Japan\'s Okinawa island believe everyone has an ikigai — a reason to live. Find yours and live life to the fullest.','assets/images/Non-Fiction & Self Help/Ikigai.jpg'),
('The Subtle Art of Not Giving a F*ck','Mark Manson','Non-Fiction & Self Help',21.90,18,'A counterintuitive approach to living a good life. Mark Manson argues that improving our lives hinges on learning to stomach lemons better. Over 10 million copies sold.','assets/images/Non-Fiction & Self Help/The Subtle Art of Not Giving a Fk.jpg'),
('How to Win Friends and Influence People','Dale Carnegie','Non-Fiction & Self Help',16.90,25,'The all-time classic guide to getting what you want out of life. For more than sixty years this book has carried thousands of people up the ladder of success.','assets/images/Non-Fiction & Self Help/How to Win Friends and Influence People.jpg');

-- Science & Technology
INSERT INTO Products (title, author, genre, price, quantity, description, cover_image) VALUES
('A Brief History of Time','Stephen Hawking','Science & Technology',19.90,20,'Was there a beginning of time? Could time run backwards? A landmark volume in science writing that has sold over 10 million copies.','assets/images/Science & Technology/A Brief History of Time.jpg'),
('Zero to One','Peter Thiel','Science & Technology',24.90,22,'Notes on startups, or how to build the future. Every moment in business happens only once. This book is about how to build companies that create new things.','assets/images/Science & Technology/Zero to One.jpg'),
('The Code Book','Simon Singh','Science & Technology',21.90,2,'A history of man\'s urge to uncover the secrets of codes, from Egyptian puzzles to modern encryption. A journey through the hidden world of ciphers and their breaking.','assets/images/Science & Technology/The Code Book.jpg'),
('Clean Code','Robert C. Martin','Science & Technology',42.90,15,'Even bad code can function. But if code isn\'t clean, it can bring a development organisation to its knees. A must for any developer interested in producing better code.','assets/images/Science & Technology/Clean Code.jpg'),
('The Pragmatic Programmer','David Thomas & Andrew Hunt','Science & Technology',44.90,12,'Straight from the programming trenches, this book examines the core process of taking a requirement and producing working, maintainable code.','assets/images/Science & Technology/The Pragmatic Programmer.jpg'),
('Life 3.0','Max Tegmark','Science & Technology',23.90,14,'How will artificial intelligence affect crime, war, justice, jobs, and our very sense of being human? An MIT professor explores the future of AI.','assets/images/Science & Technology/Life 3.0.jpg'),
('The Innovators','Walter Isaacson','Science & Technology',29.90,10,'The story of the people who created the computer and the internet. Walter Isaacson tells the intertwined lives of inventors and entrepreneurs who built the digital revolution.','assets/images/Science & Technology/The Innovators.jpg'),
('The Design of Everyday Things','Don Norman','Science & Technology',32.90,8,'A classic updated to include smartphones and the internet of things. Teaches us what design can and should be. A must-read for anyone designing or using everyday objects.','assets/images/Science & Technology/The Design of Everyday Things.jpg'),
('Surely You\'re Joking, Mr. Feynman!','Richard P. Feynman','Science & Technology',22.90,17,'Richard Feynman recounts his experience as a physicist, from cracking safes at Los Alamos to discoveries in quantum electrodynamics. A classic bestseller.','assets/images/Science & Technology/Surely You\'re Joking, Mr. Feynman!.jpg'),
('The Selfish Gene','Richard Dawkins','Science & Technology',21.90,11,'Richard Dawkins\' brilliant reformulation of the theory of natural selection. One of the most influential books ever written about genes and ourselves.','assets/images/Science & Technology/The Selfish Gene.jpg');

-- ── FAQ entries ───────────────────────────────────────────────
INSERT INTO FAQ (question, answer, category, display_order) VALUES
('How long does delivery take?','We deliver to all addresses in Singapore within 3–5 business days. Orders placed before 2 PM on a weekday are typically dispatched the same day.','Orders & Delivery',10),
('Is there free delivery?','Yes! Enjoy free delivery on all orders above SGD 50. A flat delivery fee of SGD 3.99 applies to orders below that threshold.','Orders & Delivery',20),
('Can I track my order?','Yes. Once your order has been dispatched, you can track its status from the My Orders page after logging in.','Orders & Delivery',30),
('Can I change or cancel my order?','Orders can be cancelled within 1 hour of placement provided they have not been dispatched. Email us at hello@atheneum.sg as soon as possible.','Orders & Delivery',40),
('What is your return policy?','We accept returns within 14 days of delivery. The book must be in its original, unread condition. Email hello@atheneum.sg with your order number.','Returns & Refunds',50),
('How long does a refund take?','Refunds are processed within 5–7 business days of us receiving the returned item.','Returns & Refunds',60),
('How do I leave a review?','You can leave a review after purchasing a book and receiving delivery. Visit the book page and scroll to Customer Reviews — a form will appear if you are eligible.','Account & Reviews',70),
('How do I create an account?','Click Sign Up in the top navigation bar, fill in your details and submit the form.','Account & Reviews',80),
('I forgot my password. What do I do?','On the login page, click Forgot Password and enter your registered email. You will receive a reset link within a few minutes.','Account & Reviews',90),
('What payment methods do you accept?','We accept Visa and Mastercard credit and debit cards processed securely through Stripe.','Payment',100),
('Is my payment information secure?','Yes. All payment processing is handled by Stripe, a PCI DSS-compliant provider. We never store your card number or CVV.','Payment',110),
('Do you carry e-books or audiobooks?','Currently Atheneum carries physical books only. We hope to offer e-books in the future.','General',120),
('How can I contact customer support?','Email us at hello@atheneum.sg or call +65 6234 5678 Mon–Fri 9 am–6 pm SGT. We reply within one business day.','General',130);
