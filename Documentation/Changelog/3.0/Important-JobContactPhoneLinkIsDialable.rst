.. _important-job-contact-phone-link-is-dialable:

=================================================
Important: The job contact phone link is dialable
=================================================

Description
===========

The contact block of the job detail view wrote the stored contact phone number
into the link target unchanged, so a number stored the way a reader wants to
read it produced a target a device cannot act on: ``+49 89 1234`` became
:html:`href="tel:+49 89 1234"`. The spaces are now removed from the target,
exactly as :php:`EXT:academic_persons` has always done it for the phone numbers
of a profile; the visible link text keeps the stored spelling.

..  code-block:: html

    before: <a href="tel:+49 89 1234">+49 89 1234</a>
    after:  <a href="tel:+49891234">+49 89 1234</a>

The correction is about ordinary spaces and nothing else. What an editor types
is still what is stored, and it is still what the detail view and the new-job
form display. A number that carries other characters a dialer rejects — a slash
in ``089/1234``, or a non-breaking space left behind by a copy-paste — was
undialable before and stays so.

Impact
======

The markup, its classes and the link text are unchanged; only the value of the
:html:`href` attribute is. Nothing has to be configured or migrated.

An installation that overrides
:file:`Resources/Private/Partials/Job/Contact.html` in its own site package
keeps its own output, and its own defect, until it drops the copy or applies
the same correction.

Affected Installations
======================

Installations that display a job detail view whose jobs carry a contact phone
number written with spaces.

.. index:: Frontend, ext:academic_jobs
