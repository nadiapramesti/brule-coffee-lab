<?php

/* English copy for the help and policy pages. Placeholders :flat, :free, :cod, :wa, :email, :address come from Store Settings. */
return [
    'faq' => [
        'title' => 'Help (FAQ)',
        'intro' => 'Answers to the questions we get most. Not here? Reach us on WhatsApp :wa or at :email.',
        'groups' => [
            ['title' => 'Ordering', 'items' => [
                ['q' => 'How do I order?', 'a' => 'Open the Roast builder, pick a coffee, roast level, grind and weight, then press "Add to cart". Add more coffees if you like, then Checkout. You need an account to check out; anything in your cart before you log in is kept.'],
                ['q' => 'Why is my coffee roasted after I order?', 'a' => 'So it reaches you fresh. Beans are roasted to your choice once your order joins the queue, not pulled from old roasted stock.'],
                ['q' => 'How long until my order ships?', 'a' => 'It ships within 24 hours after the order joins the roasting queue (once paid, or immediately for COD). Follow each stage on the Track order page.'],
                ['q' => 'Which weights and grinds are available?', 'a' => 'Weights: 200 g, 500 g or 1 kg. Grinds: whole bean, V60 / pour over, espresso, French press, Turkish-style (tubruk) and Aeropress.'],
                ['q' => 'Can I buy several coffees at once?', 'a' => 'Yes. Put them all in the cart (up to 10 lines, up to 10 bags per line). Everything ships in one parcel with one shipping fee.'],
            ]],
            ['title' => 'Payment', 'items' => [
                ['q' => 'Which payment methods are available?', 'a' => 'Pay online, or COD (cash to the courier when the parcel arrives). :cod'],
                ['q' => 'How does COD work?', 'a' => 'COD orders join the roasting queue right away without waiting for payment. Have the exact total in cash ready when the parcel arrives. The amount due is printed on the parcel label.'],
                ['q' => 'How do I use a voucher?', 'a' => 'Type the code in the voucher field at checkout and press "Apply". One code per order. If a code cannot be used yet, we show why, for example a minimum spend or a usage limit.'],
                ['q' => 'Can the price change after checkout?', 'a' => 'No. The system recalculates the price when the order is created and it does not change afterwards.'],
            ]],
            ['title' => 'Shipping', 'items' => [
                ['q' => 'How much is shipping?', 'a' => 'Flat Rp:flat per order. :free'],
                ['q' => 'How do I track my order?', 'a' => 'Enter your order code (format KRM-DATE-NUMBER) on the Track order page. Stages: In queue, Roasting, Grinding, Packing, Shipped, Completed. The tracking number appears as soon as the parcel ships.'],
                ['q' => 'I typed the wrong address. What now?', 'a' => 'Message us on WhatsApp :wa as soon as possible, before the order is Shipped, with your order code.'],
            ]],
            ['title' => 'Cancellation', 'items' => [
                ['q' => 'How do I cancel an order?', 'a' => 'An order still Awaiting payment can be cancelled by you on the Track order page. Once it is in the queue, contact us on WhatsApp :wa while the beans are not yet roasted. Roasted orders cannot be cancelled because the coffee is made just for you.'],
                ['q' => 'Is the stock released when I cancel?', 'a' => 'Yes. Beans held for your order go back to stock, and a voucher you used becomes usable again.'],
            ]],
            ['title' => 'About the coffee', 'items' => [
                ['q' => 'What is the difference between roast levels?', 'a' => 'Lighter roasts show more of the bean\'s own character (floral, fruity, bright acidity). Darker roasts bring more chocolate, nut and bittersweet roast flavour. The Agtron score in the builder shows darkness: a lower number means darker.'],
                ['q' => 'How should I store the coffee?', 'a' => 'Keep it in a sealed bag or container, somewhere cool and dry, away from sunlight. Avoid the fridge because of moisture. Flavour is usually best within 2 to 6 weeks of the roast date.'],
                ['q' => 'Which grind suits my brewer?', 'a' => 'V60 / pour over: medium-fine. Espresso: fine. French press: coarse. Tubruk: fine, like powder. Aeropress: medium-fine. No grinder? Choose the grind for your brewer, or whole bean if you grind yourself.'],
            ]],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy Policy',
        'updated' => 'Last updated: 17 October 2026',
        'intro' => 'We only collect the data we need to process your order.',
        'sections' => [
            ['h' => 'Data we collect', 'p' => ['When you sign up and order, we store your name, email, WhatsApp number, delivery address and order history. If you sign in with Google, we receive your name and email from Google; we never receive your Google password.']],
            ['h' => 'What we use it for', 'li' => ['Processing, roasting and shipping your order.', 'Contacting you about your order by WhatsApp or email.', 'Sending order status notifications inside the site.', 'Internal sales reports for the shop. We do not sell your data.']],
            ['h' => 'Who we share it with', 'p' => ['Your address and phone number go to the courier only so the parcel arrives. We do not sell or rent your data.']],
            ['h' => 'Cookies and browser storage', 'p' => ['We use a session cookie so you stay signed in and your cart is kept. Your theme (light/dark) and language choices are stored in your browser. We do not use advertising trackers.']],
            ['h' => 'Security', 'p' => ['Passwords are stored hashed, never as plain text. Admin access is limited to authorised staff, and important changes are written to an activity log.']],
            ['h' => 'Your rights', 'p' => ['You can ask for a copy, a correction or deletion of your data by writing to :email. Order records needed for bookkeeping may be kept only as far as that purpose requires.']],
        ],
    ],

    'terms' => [
        'title' => 'Terms & Conditions',
        'updated' => 'Last updated: 17 October 2026',
        'intro' => 'By placing an order on this site you agree to the terms below.',
        'sections' => [
            ['h' => 'Orders and prices', 'p' => ['All prices are in Indonesian Rupiah. The system recalculates prices when the order is created and they do not change afterwards. We may refuse or cancel an order if stock is insufficient or the order details are incomplete.']],
            ['h' => 'Coffee made to order', 'p' => ['Coffee is roasted and ground after your order joins the queue, to your selection. For that reason an order cannot be cancelled once the beans start roasting.']],
            ['h' => 'Payment', 'li' => ['Pay online: the order joins the queue once payment is confirmed.', 'COD: you pay the courier the total amount in cash when the parcel is received. COD orders that are refused or not collected without a valid reason may restrict your future use of COD.', ':cod']],
            ['h' => 'Vouchers', 'p' => ['Vouchers have conditions (such as a minimum spend, an expiry date or a usage limit) and cannot be exchanged for cash. One code per order.']],
            ['h' => 'Shipping', 'p' => ['Shipping terms, fees and damage claims are on the Shipping & Returns page.']],
            ['h' => 'Accounts', 'p' => ['You are responsible for keeping your account secure and for the accuracy of the details you enter. Do not share your password.']],
            ['h' => 'Changes to these terms', 'p' => ['We may update these terms at any time. The latest version is always on this page; existing orders follow the terms in force when the order was placed.']],
            ['h' => 'Contact', 'p' => ['Questions about these terms: :email or WhatsApp :wa.']],
        ],
    ],

    'shipping' => [
        'title' => 'Shipping & Returns',
        'updated' => 'Last updated: 17 October 2026',
        'intro' => 'How we ship your coffee, and what to do if something goes wrong.',
        'sections' => [
            ['h' => 'Processing and dispatch', 'p' => ['Orders are roasted after they join the queue and ship within 24 hours. The tracking number appears on the Track order page as soon as the parcel ships. Parcels ship from :address.']],
            ['h' => 'Shipping fee', 'p' => ['Flat Rp:flat per order, however many items it contains. :free']],
            ['h' => 'If the parcel is damaged or wrong', 'p' => ['Contact us within 2 x 24 hours of delivery on WhatsApp :wa or at :email. Include your order code and a photo or video of the parcel being opened. If it was damaged in transit or the item sent does not match your order, we send a replacement or refund you.']],
            ['h' => 'Returns for taste', 'p' => ['Coffee is a food product made specially to your order, so we do not accept returns because of taste or choosing the wrong roast level or grind. If it was not quite right, tell us and we will help you pick a better combination next time.']],
            ['h' => 'Cancellation', 'p' => ['An order can be cancelled while the beans are not yet roasted. See the FAQ for how.']],
        ],
    ],
];
