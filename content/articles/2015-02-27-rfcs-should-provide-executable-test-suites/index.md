+++
title = "RFCs should provide executable test suites"
date = "2015-02-27"
[taxonomies]
keywords=["test", "rfc"]
[extra]
pinned = true
+++

Recently, I implemented xCal and xCard formats inside the sabre/dav
libraries. While testing the different RFCs against my implementation,
several errata have been found. This article, first, quickly list them
and, second, ask questions about how such errors can be present and how
they can be easily revealed. If reading my dry humor about RFC errata is
boring, the Sections 3, 4 and 5 are more interesting. The whole idea is:
Why RFCs do not provide executable test suites?

## What is xCal and xCard?

The Web is a read-only media. It is based on the HTTP protocol. However,
there is the [WebDAV](https://en.wikipedia.org/wiki/WebDAV) protocol,
standing for Web Distributed Authoring and Versioning. This is an
extension to HTTP. *Et voilà !* The Web is a read and write media.
WebDAV is standardized in [RFC2518](https://tools.ietf.org/html/rfc2518)
and [RFC4918](https://tools.ietf.org/html/rfc4918).

Based on WebDAV, we have [CalDAV](https://en.wikipedia.org/wiki/CalDAV)
and [CardDAV](https://en.wikipedia.org/wiki/CardDAV), respectively for
reading and writing calendars and addressbooks. They are standardized in
[RFC4791](https://tools.ietf.org/html/rfc4791),
[RFC6638](https://tools.ietf.org/html/rfc6638) and
[RFC6352](https://tools.ietf.org/html/rfc6352). Good! But these
protocols only explain how to read and write, not how to represent a
real calendar or an addressbook. So let's leave protocols for formats.

The [iCalendar](https://en.wikipedia.org/wiki/ICalendar) format
represents calendar events, like events (`VEVENT`), tasks (`VTODO`),
journal entry (`VJOURNAL`, very rare…), free/busy time (`VFREEBUSY`)
etc. The [vCard](https://en.wikipedia.org/wiki/VCard) format represents
cards. The formats are very similar and share a common ancestry: This is
a **horrible** line-, colon- and semicolon-, randomly-escaped based
format. For instance:

```
BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
PRODID:-//Example Inc.//Example Calendar//EN
BEGIN:VEVENT
DTSTAMP:20080205T191224Z
DTSTART;VALUE=DATE:20081006
SUMMARY:Planning meeting
UID:4088E990AD89CB3DBB484909
END:VEVENT
END:VCALENDAR
```

Horrible, yes. You were warned. These formats are standardized in
several RFCs, to list some of them:
[RFC5545](https://tools.ietf.org/html/rfc5545),
[RFC2426](http://tools.ietf.org/html/rfc2426) and
[RFC6350](http://tools.ietf.org/html/rfc6350).

This format is impossible to read, even for a computer. That's why we
have jCal and jCard, which are respectively another representation of
iCalendar and vCard but in [JSON](http://json.org/). JSON is quite
popular in the Web today, especially because it eases the manipulation
and exchange of data in Javascript. This is just a very simple, and
—from my point of view— human readable, serialization format. jCal and
jCard are respectively standardized in
[RFC7265](http://tools.ietf.org/html/rfc7265) and
[RFC7095](http://tools.ietf.org/html/rfc7095). Thus, the equivalent of
the previous iCalendar example in jCal is:

```json
[
    "vcalendar",
    [
        ["version", {}, "text", "2.0"],
        ["calscale", {}, "text", "GREGORIAN"],
        ["prodid", {}, "text", "-\/\/Example Inc.\/\/Example Calendar\/\/EN"]
    ],
    [
        [
            "vevent",
            [
                ["dtstamp", {}, "date-time", "2008-02-05T19:12:24Z"],
                ["dtstart", {}, "date", "2008-10-06"],
                ["summary", {}, "text", "Planning meeting"],
                ["uid", {}, "text", "4088E990AD89CB3DBB484909"]
            ]
        ]
    ]
]
```

Much better. But this is JSON, which is a rather loose format, so we
also have xCal and xCard another representation of iCalendar and vCard
but in [XML](https://en.wikipedia.org/wiki/XML). They are standardized
in [RFC6321](https://tools.ietf.org/html/rfc6321) and
[RFC6351](https://tools.ietf.org/html/rfc6351). The same example in xCal
looks like this:

```xml
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
  <vcalendar>
    <properties>
      <version>
        <text>2.0</text>
      </version>
      <calscale>
        <text>GREGORIAN</text>
      </calscale>
      <prodid>
        <text>-//Example Inc.//Example Calendar//EN</text>
      </prodid>
    </properties>
    <components>
      <vevent>
        <properties>
          <dtstamp>
            <date-time>2008-02-05T19:12:24Z</date-time>
          </dtstamp>
          <dtstart>
            <date>2008-10-06</date>
          </dtstart>
          <summary>
            <text>Planning meeting</text>
          </summary>
          <uid>
            <text>4088E990AD89CB3DBB484909</text>
          </uid>
        </properties>
      </vevent>
    </components>
  </vcalendar>
</icalendar>
```

More semantics, more meaning, easier to read (from my point of view),
namespaces… It is very easy to **embed** xCal and xCard inside other XML
formats.

Managing all these formats is an extremely laborious task. I suggest you
to take a look at [`sabre/vobject`](http://sabre.io/vobject/) (see [the
Github repository of
`sabre/vobject`](https://github.com/fruux/sabre-vobject/)). This is a
PHP library to manage all the weird formats. The following example shows
how to read from iCalendar and write to jCal and xCal:

```php
<?php

// Read iCalendar.
$document = Sabre\VObject\Reader::read($icalendar);

// Write jCal.
echo Sabre\VObject\Writer::writeJson($document);

// Write xCal.
echo Sabre\VObject\Writer::writeXml($document);
```

Magic when you know the complexity of these formats (in both term of
parsing and validation)!

## List of errata

Now, let's talk about all the errata I submited recently:

- [4241, in
  RFC6351](http://www.rfc-editor.org/errata_search.php?eid=4241)
  (xCard),
- [4243, in
  RFC6351](http://www.rfc-editor.org/errata_search.php?eid=4243)
  (xCard),
- [4246, in
  RFC6350](http://www.rfc-editor.org/errata_search.php?eid=4246)
  (vCard),
- [4247, in
  RFC6351](http://www.rfc-editor.org/errata_search.php?eid=4247)
  (xCard),
- [4245, in
  RFC6350](http://www.rfc-editor.org/errata_search.php?eid=4245)
  (vCard),
- [4261, in
  RFC6350](http://www.rfc-editor.org/errata_search.php?eid=4261)
  (vCard).

The 2 last ones are reported, not yet verified.

4241, 4243 and 4246 are just typos in examples. “*just*” is a bit of an
under-statement when you are reading RFCs for days straight, you have 10
of them opened in your browser and trying to figure out how everything
fits together and if you are doing everything correctly. Finding typos
at that point in your process can be very confusing…

4247 is more subtle. The RFC about xCard comes with an [XML
Schema](https://en.wikipedia.org/wiki/XML_Schema_%28W3C%29). That's
great! It will help us to test our documents and see if they are valid
or not! No? No.

Most of the time, I try to relax and deal with the incoming problems.
But the date and time format in iCalendar, vCard, jCal, jCard, xCal and
xCard can make my blood boil in a second. In what world, exactly, `--10`
or `---28` is a conceivable date and time format? How long did I sleep?
“Well” — was I saying to myself, “do not make a drama, we have the XML
Schema!”. No. Because there is an error in the schema. More precisely,
in a regular expression:

```
value-time = element time {
    xsd:string { pattern = "(\d\d(\d\d(\d\d)?)?|-\d\d(\d\d?)|--\d\d)"
                         ~ "(Z|[+\-]\d\d(\d\d)?)?" }
}
```

Did you find the error? `(\d\d?)` is invalid, this is `(\d\d)?`. Don't
get me wrong: Everyone makes mistakes, but not this kind of error. I
will explain why in the next section.

4245 is not an editorial error but a technical one, under review.

4261 is crazy. It deserves a whole sub-section.

### Welcome in the crazy world of date and time formats

There are two major popular date and time format:
[RFC2822](http://tools.ietf.org/html/rfc2822) and ISO.8601. Examples:

- `Fri, 27 Feb 2015 16:06:58 +0100` and
- `2015-02-27T16:07:16+01:00`.

The second one is a good candidate for a computer representation: no
locale, only digits, all information are present…

Maybe you noticed there is no link on ISO.8601. Why? Because ISO
standards are not free and I don't want [to pay
140€](http://www.iso.org/iso/catalogue_detail?csnumber=40874) to buy a
standard…

The date and time format adopted by iCalendar and vCard (and the rest of
the family) is ISO.8601.2004. I cannot read it. However, since we said
in xCard we have an XML Schema; we can read this (after having applied
erratum 4247):

```
# 4.3.1
value-date = element date {
    xsd:string { pattern = "\d{8}|\d{4}-\d\d|--\d\d(\d\d)?|---\d\d" }
}

# 4.3.2
value-time = element time {
    xsd:string { pattern = "(\d\d(\d\d(\d\d)?)?|-\d\d(\d\d)?|--\d\d)"
                         ~ "(Z|[+\-]\d\d(\d\d)?)?" }
}

# 4.3.3
value-date-time = element date-time {
    xsd:string { pattern = "(\d{8}|--\d{4}|---\d\d)T\d\d(\d\d(\d\d)?)?"
                         ~ "(Z|[+\-]\d\d(\d\d)?)?" }
}

# 4.3.4
value-date-and-or-time = value-date | value-date-time | value-time
```

Question: **`--10` is October or 10 seconds**?

`--10` can fit into `value-date` and `value-time`:

- From `value-date`, the 3rd element in the disjunction is
  `--\d\d(\d\d)?`, so it matches `--10`,
- From `value-time`, the last element in the first disjunction is
  `--\d\d`, so it matches `--10`.

If we have a date-and-or-time value, `value-date` comes first, so `--10`
is always October. Nevertheless, if we have a time value, `--10` is
10 seconds. Crazy now?

Oh, and XML has its own date and time format, which is well-defined and
standardized. Why should we drag this crazy format along?

Oh, and I assume every format depending on ISO.8601.2004 has this bug.
But I am not sure because ISO standards are not free.

## How can RFCs have such errors?

So far, RFCs are textual standards. Great. But they are just text.
Written by humans, and thus they are subject to errors or failures. It
is even error-prone. I do not understand: Why an RFC does not come with
an **executable test suite**? I am pretty sure every reader of an RFC
will try to create a test suite on its own.

I assume xCal and xCard formats are not yet very popular. Consequently,
few people read the RFC and tried to write an implementation. This is my
guess. However, it does not avoid the fact an executable test suite
should (must?) be provided.

## How did I find them?

This is how I found these errors. I wrote [a test suite for xCal and
xCard in
`sabre/vobject`](https://github.com/fruux/sabre-vobject/blob/master/tests/VObject/Parser/XmlTest.php).
I would love to write a test suite agnostic of the implementation, but I
ran out of time. This is basically format transformation: R:x→y where R
can be a reflexive operator or not (depending of the versions of
iCalendar and vCard we consider).

For “simple“ errata, I found the errors by testing it manually. For
errata 4247 and 4261 (with the regular expressions), I found the error
by applying the algorithms presented in [Generate strings based on
regular
expressions](@/articles/2014-09-30-generate-strings-based-on-regular-expressions/index.md).

## Conclusion

`sabre/vobject` supports xCal and xCard.
