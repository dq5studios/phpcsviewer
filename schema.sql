--
-- Sniffs
--
CREATE SEQUENCE sniff_seq
    START WITH 1000
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE sniff
(
    seq integer DEFAULT nextval('sniff_seq'::regclass) NOT NULL,
    id character varying(100) NOT NULL,
    descrip text NOT NULL,
    parent character varying(100),
    added text NOT NULL,
    removed text
);

ALTER TABLE ONLY sniff
    ADD CONSTRAINT sniff_pkey PRIMARY KEY (seq);


--
-- Sniff opts
--
CREATE SEQUENCE sniff_opt_seq
    START WITH 1000
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE sniff_opt
(
    seq integer DEFAULT nextval('sniff_opt_seq'::regclass) NOT NULL,
    sniff_seq integer NOT NULL,
    name character varying(100) NOT NULL,
    descrip text NOT NULL,
    def character varying(100) NOT NULL,
    type character varying(100) NOT NULL
);

ALTER TABLE ONLY sniff_opt
    ADD CONSTRAINT sniff_opt_pkey PRIMARY KEY (seq);

ALTER TABLE ONLY sniff_opt
    ADD CONSTRAINT sniff_opt_fkey FOREIGN KEY (sniff_seq) REFERENCES sniff(seq);


-- versions table
