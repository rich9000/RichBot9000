# RMA Software

Database
dev database is on laptop, 
pma:Looper-890 - user fro phpMyAdmin
rma:rma_User-123 - db user from software
sudo mysql - to get root access to mySQL

## Tables

### RMAs (rma_info)

* UID - Unique ID
* Created date - record create date
* Modified date
* parts received date 
* RMA# - number issued by ACM
* Contact UID
* Address UID
* shipped date??
* tracking Number
* shipped description
* Notes
* user created
* user modified

### Returned Parts (ret_parts)

* UID
* RMA# (UID)- from RMAs table
* Part Desc (UID)
* reference number?  (last 5 of RMA - 000, ie 0538-002) used for QR ?
* Issue Code (UID)
* created time
* user created
* modified time
* user modified

### Part Desc (part_desc)

* UID
* PN
* Description
* pic loc
* created time
* user created
* modified time
* user modified

### Issue Codes (code)

* UID
* code (OK, PD, B, LED, CF etc)
* description
* created time
* user created
* modified time
* user modified

### Shipping Addresses (addr)

* UID
* RMA UID
* Building code (AKC1, GYR1, …)
* address parts
* created time
* user created
* modified time
* user modified

### Site Contacts

* UID
* name
* email
* site
* more stuff
* created time
* user created
* modified time
* user modified


### Users
* UID
* name
* created time
* user created
* modified time
* user modified
* more user stuff

## Pages
1. Open RMAs,
2. Lookup by RMA
3. Addresses
4. Site contacts
5. new RMA
6. add parts and qtys
7. assign loc
8. Update parts given RMA

[BACK](./README.md)