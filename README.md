## Assumptions
 - Certain products may have certain questions that are required (domain knowledge), ensure these are still in the 
   journey.
 - Journeys map to product lines.

## Solution

## Trade off's

### Questions
- Questions are shared, traded off between being a domain knowledge only of a wizzard.
- Both Product and Questions need to be able to have knoweldge of the questions.
- Could potentially be bounded context within jounry/wizzard - and a port to access the questions.

### Validation
- Slows the process down as cross-team (product/journey)
- However, is vital in preventing errors being introduced into the journey.

### Versioning rollback
- Rollback always increasing the versioning creates a new row all the time, this makes at a code level easier to just
  'Always move forward'. We are creating more spaces in the database when we may not need to. Also reporting suffers a
   bit here potentially as if we 'rollback' we then have 2 versions that are the same.
- Tradeoff is that no 'audit log' would be required to keep track of the change and who by. Handled automatically
  with a new record. Table bloat?

## Prompt Log
```
We need admins to be able to create draft versions of the wizards. Add a JourneyPublication table containing at publish time,     
serialize the current questions/copy into one JSON field, version number, published_at, published_by, and a status (enum either   
draft or publish). In the UI, we then need the ability to cerate a draft from a current journey, amend it, and publish it when
```

```
 Next we need to add a rollback feature. This will work by duplicating the previous version into a new Republish-as-new-version,   
 so snapshotting the previous version, add a field called rollback default to false, set to true on rollback, and display in the   
 versions manager - ensure we reuse code where possible.
```

```
Next we need a table and filament resource to create 'products' (id, name). Adding a key onto the questions table to uniqeluey    
identify a question, we need the ability to set a list of (array on the products) required questions.
When editing a product / creating - please select a wizzard first and populate the questions based on that, we CANT have          
questions cross wizzard 
```

## Evolving
- Multi validation of published draft before being able to publish.
- Questions available to multiple journeys - a 'bank' of questions that may be used across multiple journeys.
- Ability to roll back multiple versions with the republish as new concept.
- Display the list of differences in the journeys before publishing.
- Ability to 'view' the old versions of a journey.
